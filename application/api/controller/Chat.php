<?php
/**
 * Created by PhpStorm.
 * User: GYW
 * Date: 2019/7/28
 * Time: 15:49
 */

namespace app\api\controller;

use think\Controller;
use think\Request;

class Chat extends Controller
{
    /**
     * 文本消息的数据持久化
     */
    public function saveMessage()
    {
        $message = Request::instance()->post(false);
        $data['fromid'] = $message['from_id'];
        $data['fromname'] = $this->getName($data['fromid']);
        $data['toid'] = $message['to_id'];
        $data['toname'] = $this->getName($data['toid']);
        $data['content'] = $message['data'];
        $data['time'] = $message['time'];
        $data['isread'] = $message['isread'];
        $data['type'] = 1;

        db('communication')->insert($data);

    }


    /**
     * 根据用户id获取用户昵称
     */
    public function getName($uid)
    {
        $name = db('user')->where('id', $uid)->value('nickname');
        return $name;
    }


    /**
     * 根据用户id获取聊天双方头像信息
     */
    public function getHead()
    {
        $req = Request::instance()->post(false);
        $from_id = $req['from_id'];
        $to_id = $req['to_id'];

        $from_info = db('user')->where('id', $from_id)->field('headimgurl')->find();
        $to_info = db('user')->where('id', $to_id)->field('headimgurl')->find();

        return [
            'from_headimg' => $from_info['headimgurl'],
            'to_headimg' => $to_info['headimgurl']
        ];
    }

    /**
     * 获取聊天双方的聊天记录
     */
    public function load()
    {
        $req = Request::instance()->post(false);
        $from_id = $req['from_id'];
        $to_id = $req['to_id'];

        $count = db('communication')->where('(fromid = :fromid and toid = :toid)||(fromid = :toid1 and toid = :fromid1)',
            ['fromid' => $from_id, 'toid' => $to_id, 'toid1' => $to_id, 'fromid1' => $from_id])->count('id');

        //聊天记录最多显示10条
        if ($count >= 10) {
            $message = db('communication')->where('(fromid = :fromid and toid = :toid)||(fromid = :toid1 and toid = :fromid1)',
                ['fromid' => $from_id, 'toid' => $to_id, 'toid1' => $to_id, 'fromid1' => $from_id])->limit($count - 10, 10)->order('id')->select();
        } else {
            $message = db('communication')->where('(fromid = :fromid and toid = :toid)||(fromid = :toid1 and toid = :fromid1)',
                ['fromid' => $from_id, 'toid' => $to_id, 'toid1' => $to_id, 'fromid1' => $from_id])->select();
        }

        return $message;
    }

    /**
     * 上传图片，返回图片地址
     */
    public function uploadImg()
    {
        $file = Request::instance()->file('file');
        $req = Request::instance()->post(false);
        $from_id = $req['from_id'];
        $to_id = $req['to_id'];
        $online = $req['online'];


        if ($file) {
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                $img_name = $info->getSaveName();
//                return $info->getSaveName();
                $data['content'] = $img_name;
                $data['fromid'] = $from_id;
                $data['toid'] = $to_id;
                $data['isread'] = $online;
                $data['fromname'] = $this->getName($data['fromid']);
                $data['toname'] = $this->getName($data['toid']);
                $data['type'] = 2;
                $data['time'] = time();

                db('communication')->insert($data);

                return ['status' => 'ok','img_name' => $img_name];
            }
        }
    }


    /**
     * @param $uid
     * 根据uid获取用户头像
     */
    public function getHeadOne($uid)
    {
        $headimgurl = db('user')->where('id',$uid)->value('headimgurl');
        return $headimgurl;
    }


    /**
     * @param $from_id
     * @param $to_id
     * 获取用户未读信息的条数
     */
    public function getCountNoread($from_id, $to_id)
    {
        $count = db('communication')->where([
            'fromid' => $from_id,
            'toid'   => $to_id,
            'isread' => 0
        ])->count('id');

        return $count;
    }


    /**
     * @param $from_id
     * @param $to_id
     * 获取聊天时最新的一条消息
     */
    public function getLastMessage($from_id, $to_id)
    {
        $message = db('communication')->where('(fromid = :fromid and toid = :toid)||(fromid = :toid1 and toid = :fromid1)',
            ['fromid' => $from_id, 'toid' => $to_id, 'toid1' => $to_id, 'fromid1' => $from_id])->order('id desc')->limit(1)->find();

        return $message;
    }








    /**
     * 根据fromid获取当前用户的聊天列表
     */
    public function getList()
    {
        $from_id = Request::instance()->post('id');

        $info = db('communication')->field(['fromid','toid','fromname'])->where('toid',$from_id)->group('fromid')->select();


        $rows = array_map(function($res){
            return [
                'head_url' => $this->getHeadOne($res['fromid']),
                'username' => $res['fromname'],
                'countNoread' => $this->getCountNoread($res['fromid'], $res['toid']),
                'lastMessage' => $this->getLastMessage($res['fromid'], $res['toid']),
                'chat_page' => 'http://chat.com/?from_id='.$res['toid'].'&to_id='.$res['fromid'],
            ];
        },$info);

        return $rows;



        //        return $info;


    }


}
