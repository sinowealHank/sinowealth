<?php
function get_prop_status($id = ''){
    $prop_status = db('prop_status')->select();
    $str = '';
    foreach($prop_status as $val){
        if($id == $val['id']){
            $selected = 'selected="selected"';
        }else{
            $selected = '';
        }
        $str .= '<option value="'.$val['id'].'" '.$selected.'>'.$val['prop_status'].'</option>';
    }
    return $str;
}

function get_status($id){
    $str = '';
    if($id == 1){
        $str .= '个人资产';
    }else if($id == 2){
        $str .= '<span style="color: red">公司公共资产</span>';
    }else if($id == 3){
        $str .= '报废资产';
    }else if($id == 4){
        $str .= '<span style="color: pink">部门资产</span>';
    }
    return $str;
}

function get_check($id){
    $str = '';
    if($id == 0){
       $str .= '未确认验收单';
    }else if($id == 1){
        $str .= '管理员已确认';
    }else if($id == 2){
        $str .= '已验收';
    }
    return $str;
}
//申请列表的资产类型
function get_type($id){
    $str = '';
    if($id == '1'){
        $str .= '个人资产';
    }else if($id == '2'){
        $str .= '部门公共资产';
    }else if($id == '3'){
        $str .= '公司公共资产';
    }
    return $str;
}

//采购流程
function get_pur_process($num,$id){
    $str = '';
    if($num == '0'){
        $str .= '<button class="btn btn-primary btn-xs">已申请采购单</button>';
    }else if($num == '1'){
        $str .= '<button title="修改采购单" class="btn btn-danger btn-xs layerIframe" url="editpur?id='.$id.'" max="true">IT主管不同意</button>';
    }else if($num == '2'){
        $str .= '<button class="btn btn-primary btn-xs">IT主管同意</button>';
    }else if($num == '3'){
        $str .= '<button title="修改采购单" class="btn btn-danger btn-xs layerIframe" url="editpur?id='.$id.'" max="true">申请部门主管不同意</button>';
    }else if($num == '4'){
        $str .= '<button class="btn btn-primary btn-xs">申请主管同意</button>';
    }else if($num == '5'){
        $str .= '<button title="修改采购单" class="btn btn-danger btn-xs layerIframe" url="editpur?id='.$id.'" max="true" >统购长不同意</button>';
    }else if($num == '6'){
        $str .= '<button class="btn btn-primary btn-xs">统购长不同意</button>';
    }
    return $str;
}

function get_apply_status($num){
    $str = '';
    if($str == '1'){
        $str .= '主管已同意';
    }else{
        $str .= '主管不同意';
    }
    return $str;

}

//获得用户的id
function get_userid($nickname){
//    $data = cache('user_data');
    $data = config('user_info');
    $id = '';
    foreach($data as $k => $v){
        if($v['nickname'] == $nickname){
            $id .= $v['id'];
            break;
        }
    }
    return $id;
}

//获得部门的id
function get_dep_id($en_name){
//    $data = cache('dep_data');
    $data = config('dep_info');
    $id = '';
    foreach($data as $k => $v){
        if($en_name == 'ST'){
            $en_name = '中颖电子';
            if($v['en_name'] == $en_name){
                $id .= $v['id'];
                break;
            }
        }else{
            if($v['en_name'] == $en_name){
                $id .= $v['id'];
                break;
            }

        }
    }
    return $id;
}

//获得资产的状态
function get_prop_status_num($status){
     if($status == '个人资产'){
         return $id = 1;
     }else if($status == '部门资产'){
         return $id = 2;
     }else if($status == '公司公共资产'){
         return $id = 3;
     }else if($status == '库存状态'){
         return $id = 4;
     }
}

//获得部门的en_name
function get_dep_name($id){
//    $data = cache('dep_data');
    $data = config('dep_info');
    $name = '';
    foreach($data as $k => $v){
        if($v['id'] == $id){
            $name .= $v['en_name'];
            break;
        }
    }
    return $name;
}

function get_cost_type($id){
    $data = cache('cost_type_info');
    $cost_type = '';
    foreach($data as $k => $v){
        if($v['id'] == $id){
            $cost_type .= $v['free_type_select'];
        }
    }
    return $cost_type;

}

