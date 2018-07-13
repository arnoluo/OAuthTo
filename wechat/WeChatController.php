<?php

namespace App\Http\Controllers\Front;

use App\Repositories\Admin\WeChatRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Services\WechatPayService;
use App\Models\Due;
use Cache;
use Log;

class WechatController extends Controller
{
    protected $weChatRepo;

    public function __construct(WeChatRepository $chatRepository)
    {
        $this->weChatRepo = $chatRepository;
    }

    public function payment(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        // 检查系统内部账单，避免重复处理
        $checkArray = [
            'out_trade_no' => $request->input('out_trade_no', '')
        ];

        //if ($prepayId = cache($admin->user_id . '_prepay_id', false)) {
        if ($prepayId = $request->input('prepay_id', '')) {
            $wechatPay = new WechatPayService();
            $param = $wechatPay->makeJsapiPayInfo($prepayId, $request->url());
            $param['out_trade_no'] = $request->input('out_trade_no', '');
            $param['total_fee'] = $request->input('total_fee', '');
            $param['result'] = false;
            if (Due::checkPaidBill($checkArray)) {
                $param['result'] = true;
            }
            return view('weixin.payment', $param);
        }
        
        return view('weixin.payerror', ['error' => '订单已支付或已失效']);
    }

    public function paymentNotify(Request $request)
    {
        //获取通知的数据
        $xml = file_get_contents('php://input');
        //如果返回成功则验证签名
        $wechatPay = new WechatPayService();
        $data = $wechatPay->init($xml);
        $msg = 'OK';

        // 检查回调信息
        if(!$data || !array_key_exists("transaction_id", $data)){
            $msg = "参数格式校验错误";
            logger('Check callback parameters error');
            return $wechatPay->toXml($wechatPay->paymentNotify($msg));
        }

        // 检查系统内部账单，避免重复处理
        $checkArray = [
            'final_amount' => $data['total_fee'] / 100,
            'out_trade_no' => $data['out_trade_no']
        ];
        if (Due::checkPaidBill($checkArray)) {
            $msg = '账单已处理';
            logger('System bill already paid');
            return $wechatPay->toXml($wechatPay->paymentNotify($msg));
        }

        // 核对系统未支付账单
        $currentBill = Due::checkNotPayBill($checkArray);
        if (!$currentBill) {
            $msg = '未找到系统内部账单';
            logger('System bill not exist');
            return $wechatPay->toXml($wechatPay->paymentNotify($msg));
        }

        //微信校验该订单，修改内部账单状态。
        $order = ['out_trade_no' => $data['out_trade_no']];
        $reply = $wechatPay->paymentNotify($msg, $order);
        if ($reply['return_code'] == 'SUCCESS' 
            && $reply['trade_state'] =='SUCCESS'
            && $data['result_code'] == 'SUCCESS') {

            $res = Due::billPaid($order, $data['time_end']);
            Cache::pull(admin($currentBill->user_id)->user_id . '_prepay_id');
            logger('Change system bill status', $reply);
            unset($reply['trade_state']);
        }

        return $wechatPay->toXml($reply);
    }

    public function queryOrder(Request $request)
    {
        $tradeNo = $request->input('out_trade_no', '');
        if ($tradeNo) {
            $query = ['out_trade_no' => $tradeNo];
            $wechatPay = new WechatPayService();
            $result = $wechatPay->orderQuery($query);

            return view('weixin.payinfo', $result);
        }

        return view('weixin.payerror', ['error' => '查询单号异常']);
    }

    public function closeOrder(Request $request)
    {
        $tradeNo = $request->input('out_trade_no', '');
        if ($tradeNo) {
            $query = ['out_trade_no' => $tradeNo];
            $currentBill = Due::checkNotPayBill($query);

            if (!$currentBill) {
                return view('weixin.payerror', ['error' => '该单号不存在或已支付，无法关单']);
            }

            if (time() - strtotime($currentBill->created_at) < 5 * 60) {
                return view('weixin.payerror', ['error' => '单号生成五分钟内无法关单']);
            }

            $wechatPay = new WechatPayService();
            $result = $wechatPay->closeOrder($query);

            return view('weixin.payinfo', $result);
        }

        return view('weixin.payerror', ['error' => '查询单号异常']);
    }
}
