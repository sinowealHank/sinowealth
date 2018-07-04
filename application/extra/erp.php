<?php
function get_bpm_dep_name($dep){
    if($dep == 1){
        return 'PP部门人员';
    }else if($dep == 2){
        return 'PM审核人员';
    }else if($dep == 3){
        return '维护人员';
    }else{
        return 'SD部门人员';
    }
}