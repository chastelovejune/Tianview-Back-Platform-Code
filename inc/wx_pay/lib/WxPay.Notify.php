<?php
/**
 * 
 * 回调基础类
 * @author widyhu
 *
 */
class WxPayNotify extends WxPayNotifyReply
{
	/**
	 * 
	 * 回调入口
	 * @param bool $needSign  是否需要签名输出
	 */
	final public function Handle($needSign = true)
	{
		$msg = "OK";
		//当返回false的时候，表示notify中调用NotifyCallBack回调失败获取签名校验失败，此时直接回复失败
		$result = WxpayApi::notify(array($this, 'NotifyCallBack'), $msg);
		if($result == false){
			$this->SetReturn_code("FAIL");
			$this->SetReturn_msg($msg);
			$this->ReplyNotify(false);
			return;
		} else {
			//该分支在成功回调到NotifyCallBack方法，处理完成之后流程
			$this->SetReturn_code("SUCCESS");
			$this->SetReturn_msg("OK");
		}
		$this->ReplyNotify($needSign);
	}
	
	/**
	 * 
	 * 回调方法入口，子类可重写该方法
	 * 注意：
	 * 1、微信回调超时时间为2s，建议用户使用异步处理流程，确认成功之后立刻回复微信服务器
	 * 2、微信服务器在调用失败或者接到回包为非确认包的时候，会发起重试，需确保你的回调是可以重入
	 * @param array $data 回调解释出的参数
	 * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	public function NotifyProcess($data, &$msg)
	{
		//TODO 用户基础该类之后需要重写该方法，成功的时候返回true，失败返回false
		return true;
	}
	
	/**
	 * 
	 * notify回调方法，该方法中需要赋值需要输出的参数,不可重写
	 * @param array $data
	 * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
	 */
	final public function NotifyCallBack($data)
	{
		$msg = "OK";
		$result = $this->NotifyProcess($data, $msg);
		
		if($result == true){
			$this->SetReturn_code("SUCCESS");
			$this->SetReturn_msg("OK");
		} else {
			$this->SetReturn_code("FAIL");
			$this->SetReturn_msg($msg);
		}
		return $result;
	}
	
	/**
	 * 
	 * 回复通知
	 * @param bool $needSign 是否需要签名输出
	 */
	final private function ReplyNotify($needSign = true)
	{
		//如果需要签名
		if($needSign == true && 
			$this->GetReturn_code($return_code) == "SUCCESS")
		{
			$this->SetSign();
		}
		WxpayApi::replyNotify($this->ToXml());
	}
}

class PayNotifyCallBack extends WxPayNotify
			{
				//查询订单
				public function Queryorder($transaction_id)
				{
					$input = new WxPayOrderQuery();
					$input->SetTransaction_id($transaction_id);
					$result = WxPayApi::orderQuery($input);
					Log::DEBUG("query:" . json_encode($result));
					if(array_key_exists("return_code", $result)
						&& array_key_exists("result_code", $result)
						&& $result["return_code"] == "SUCCESS"
						&& $result["result_code"] == "SUCCESS")
					{
						return true;
					}
					return false;
				}
				
				//重写回调处理函数
				public function NotifyProcess($data, &$msg)
				{
					Log::DEBUG("call back:" . json_encode($data));
					$notfiyOutput = array();
					
					if(!array_key_exists("transaction_id", $data)){
						$msg = "输入参数不正确";
						return false;
					}
					//查询订单，判断订单真实性
					if(!$this->Queryorder($data["transaction_id"])){
						$msg = "订单查询失败";
						return false;
					}
					
					$sn=$data["out_trade_no"];
					$m=$m_order=new model("orders");
					$info=$m->getOne(" order_sn='".$sn."' ");
					if($info&&($info["pay_status"]!=2))
					{
						$r=array("pay_status"=>2,"order_status"=>"200","pay_time"=>time());
						$re=$m->update($r," order_sn='".$sn."' ");
						file_put_contents(ROOT_PATH."data/pay_info.txt",$re." ".$sn." ".$m->sql);
						if($re)
						{
							//添加一条支付记录
								$i["order_sn"]=$sn;
								$i["money"]=$data["total_fee"];
								$i["pay_sn"]=date("YmdHis",time()).mt_rand(10000000,99999999);
								$i["add_time"]=time();
								$i["pay_type"]=1;
								$i["member_id"]=$info["member_id"];
								$i["flag"]=1;
								$i["mod_key"]="pay_logs";
								$i["mod_id"]=76;
								$m=new model("pay_logs");
								$m->add($i);
							
							//如果有分销商，为分销商添加一条收益记录
								if($info["r_member_id"])
								{
								}
							
							//卖家信息
								$m=new model("stores");
								$store=$m->getOne(" stores_id='".$info["store_id"]."' ");
								
								//短信通知卖家
								send_sms($store["mobile"],2,$sn);
							
								//如果卖家不能发货，通知能发货的店仓发货
									if(($store["is_shipping"]=="2")||($store["store_tools"]==2))
									{
										$shipping_store=$m->getOne(" flag='1' and is_shipping='1' and store_tools='1' ");
										
										//更新订单的发货方
										$re=$m_order->update(array("shipping_store_id"=>$shipping_store["stores_id"],"shipping_store_name"=>$shipping_store["stores_name"])," order_sn='".$sn."' ");
										if($re)
											send_sms($shipping_store["mobile"],5,$sn);	//向发货方发一条提醒短信
									}
							
								//为卖货微店添加一条收益记录
									$m=new model("goods");
									$s["account_sn"]=date("YmdHis",time()).mt_rand(10000000,99999999);
									$s["store_id"]=$store["stores_id"];
									$s["store_name"]=$store["stores_name"];
									$s["add_time"]=time();
									$s["mod_key"]="store_account_logs";
									$s["mod_id"]=74;
									$s["order_id"]=$info["orders_id"];
									$s["order_sn"]=$info["order_sn"];
									$s["remark"]="订单".$sn."获得佣金";
									$s["flag"]=1;
									$goods=json_decode($info["goods_info"],1);
									$fenxiao_amount=0;
									foreach($goods as $k=>$v)
									{
										//获得分销价
										$goods_price=$m->getOne(" goods_sn='".$v["goods_sn"]."' ");
										$fenxiao_amount+=$v["goods_number"]*$goods_price["fenxiao_price"];
									}
									$s["fenxiao_amount"]=$fenxiao_amount;	//供货的分销价
									$s["order_amount"]=$info["amount"];		//订单总额
									$s["money"]=number_format($s["order_amount"]-$s["fenxiao_amount"],2);	//应该获得的利润
									$m=new model("store_account_logs");
									$m->add($s);
						}
					}
					
					return true;
				}
			}