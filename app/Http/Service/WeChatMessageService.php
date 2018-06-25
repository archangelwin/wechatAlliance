<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/20 0020
 * Time: 15:11
 */

namespace App\Http\Service;


use App\Exceptions\ApiException;
use App\Models\TemplateKeyWord;
use App\Models\WeChatTemplate;
use GuzzleHttp\Client;

class WeChatMessageService
{
    private $client;
    private $baseUrl;
    private $token;
    private $appId;

    public function __construct($appId)
    {
        $this->client = new Client;
        $this->baseUrl = 'https://api.weixin.qq.com/cgi-bin/wxopen/template';
        $this->token = app(TokenService::class)->getAccessToken($appId);
        $this->appId = $appId;
    }

    /**
     * 初始化小程序的消息模板,给小程序添加微信消息模板
     *
     * @author yezi
     *
     * @return mixed
     * @throws ApiException
     */
    public function initAppTemplate()
    {
        $template = [];
        $keys = TemplateKeyWord::query()->get();
        foreach ($keys as $item){
            $result = $this->addTemplate($item->{TemplateKeyWord::FIELD_KEY_WORD},$item->{TemplateKeyWord::FIELD_KEY_WORD_IDS});
            if($result['errcode'] == 0){
                array_push($template,[
                    WeChatTemplate::FIELD_ID_APP=>$this->appId,
                    WeChatTemplate::FIELD_ID_TEMPLATE=>$result['template_id'],
                    WeChatTemplate::FIELD_TITLE=>$item->{TemplateKeyWord::FIELD_TITLE},
                    WeChatTemplate::FIELD_CONTENT=>$item->{TemplateKeyWord::FIELD_CONTENT}
                ]);
            }else{
                throw new ApiException('初始化错误！',500);
            }
        }

        if(!empty($template)){
            $result = WeChatTemplate::insert($template);
            if(!$result){
                throw new ApiException('初始化失败！',500);
            }
        }

        return $result;
    }

    /**
     * 获取模板标题下关键词库
     *
     * @author yezi
     *
     * @param $key
     * @return mixed
     */
    public function getKeyWorld($key)
    {
        $url = $this->baseUrl.'/library/get?access_token='.$this->token;
        $data = ['id'=>$key];

        $response = $this->client->post($url,['json'=>$data]);

        $result = json_decode((string) $response->getBody(), true);

        return $result;
    }

    /**
     * 添加模板消息
     *
     * @autor yezi
     *
     * @param $titleId
     * @param $keywordIds
     * @return mixed
     */
    public function addTemplate($titleId,$keywordIds)
    {
        $url = $this->baseUrl.'/add?access_token='.$this->token;
        $data = ['id'=>$titleId,'keyword_id_list'=>$keywordIds];
        $response = $this->client->post($url,['json'=>$data]);

        $result = json_decode((string) $response->getBody(), true);
        return $result;
    }

    /**
     * 发送模板消息
     *
     * @author yezi
     *
     * @return mixed
     */
    public function send($openId,$templateId,$content,$fromId,$page='')
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$this->token;
        $touser = $openId;

        $data = [
            'touser'=>$openId,
            'template_id'=>$templateId,
            'form_id'=>$fromId,
            'data'=>$content
        ];
        if($page){
            $data['page'] = $page;
        }

        $response = $this->client->post($url,['json'=>$data]);

        $result = json_decode((string) $response->getBody(), true);

        return $result;
    }

}