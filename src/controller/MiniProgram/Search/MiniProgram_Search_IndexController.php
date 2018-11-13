<?php
/**
 * Created by PhpStorm.
 * User: zhangjun
 * Date: 12/11/2018
 * Time: 2:06 PM
 */

class MiniProgram_Search_IndexController extends MiniProgram_BaseController
{

    private $miniProgramId = 200;
    private $defaultPageSize = 30;
    private $title = "核武搜索";
    private  $defaultLang = \Zaly\Proto\Core\UserClientLangType::UserClientLangZH;

    public function getMiniProgramId()
    {
        return $this->miniProgramId;
    }

    public function requestException($ex)
    {
        $this->showPermissionPage();
    }

    public function preRequest()
    {
    }

    public function doRequest()
    {
        header('Access-Control-Allow-Origin: *');
        $method = $_SERVER['REQUEST_METHOD'];
        $tag = __CLASS__ . "-" . __FUNCTION__;
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $params['title'] = $this->title;
        $params['loginName'] = $this->loginName;
        $for = isset($_GET['for']) ? $_GET['for'] : "index";
        $loginName = isset($_GET['key']) ?  $_GET['key'] : "";
        $params['key'] = $loginName;

        if($method == "post") {
            $page = isset($_POST['page']) ? $_POST['page']:1;
            switch ($for) {
                case "user":
                    $userList = $this->ctx->Manual_User->search($this->userId, $loginName, $page, $this->defaultPageSize);
                    echo json_encode(["data" => $userList]);
                    break;
                case "group":
                    $groupList = $this->ctx->Manual_Group->search($loginName,  $page, $this->defaultPageSize);
                    $groupList = $this->getGroupProfile($groupList);
                    echo json_encode(["data" => $groupList]);
                    break;
                case "joinGroup":
                    try{
                        $groupId = $_POST['groupId'];
                        $userIds = [$this->userId];
                        $joinNotice = "$this->loginName 通过核武搜索进入本群";
                        $this->ctx->Manual_Group->joinGroup($groupId, $userIds, $joinNotice);
                        $results = ["errorCode" => "success", "errorInfo" => ""];
                    }catch (ZalyException $ex) {
                        $results = ["errorCode" => "error", "errorInfo" => $ex->getErrInfo($this->defaultLang)];
                    }
                    echo json_encode($results);

                    break;
            }
        }else {
            switch ($for) {
                case "search":
                    $userList = $this->ctx->Manual_User->search($this->userId, $loginName, 1, 3);
                    $params['users'] = $userList;

                    $groupList = $this->ctx->Manual_Group->search($loginName, 1, 3);
                    $groupList = $this->getGroupProfile($groupList);
                    $params['groups'] = $groupList;

                    echo $this->display("miniProgram_search_searchList", $params);
                    break;
                case "user":
                    $userList = $this->ctx->Manual_User->search($this->userId, $loginName, 1, $this->defaultPageSize);
                    $params['users'] = $userList;
                    echo $this->display("miniProgram_search_userList", $params);
                    break;
                case "group":
                    $groupList = $this->ctx->Manual_Group->search($loginName, 1, $this->defaultPageSize);
                    $groupList = $this->getGroupProfile($groupList);
                    $params['groups'] = $groupList;
                    echo $this->display("miniProgram_search_groupList", $params);
                    break;
                default:
                    $config = require(dirname(__FILE__)."/recommend.php");
                    $params['recommend_groupids'] = json_encode($config);
                    echo $this->display("miniProgram_search_index", $params);
            }
        }
    }

    protected function getGroupProfile($groupLists)
    {
        $tag = __CLASS__.'->'.__FUNCTION__;
        try{
            $ownerIds = [];
            $groupIds = [];
            foreach ($groupLists as $key => $group) {
                $ownerIds[] = $group['owner'];
                $groupIds[] = $group['groupId'];
            }

            $ownerIds = array_unique($ownerIds);

            $list = $this->ctx->Manual_User->getProfiles($this->userId, $ownerIds);

            $userList = array_column($list, "loginName", "userId");

            $list = $this->ctx->Manual_Group->getProfiles($this->userId, $groupIds);
            $memberInGroupList = array_column($list, "isMember", "groupId");

            foreach ($groupLists as $key => $group) {
                $group['ownerName'] = $userList[$group['owner']];
                $group['isMember']  =  $memberInGroupList[$group['groupId']];
                $groupLists[$key] = $group;
            }

            return $groupLists;
        }catch (Exception $ex) {
            $this->ctx->getLogger()->error($tag, $ex);
        }
        return $groupLists;

    }

}