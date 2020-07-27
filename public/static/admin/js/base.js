// 提示信息
$(".qg-tooltip").tooltip();

// 提交请求到后台
function ajaxPost(url , data , suc , err){
    $.ajax({
        url: url,
        type: "POST",
        dataType: "JSON",
        data: data,
        success: function (result)
        {
            // 提交成功
            if (result.code == 0)
            {
                // 成功方法
                if(typeof suc =="function"){
                    suc(result.data);
                }else{
                    // 重新加载父页面
                    window.parent.location.reload(true);
                }
            } else if (result.code == -2){
                layer.msg("登录过期");
            } else {
                // 错误方法
                if(typeof err =="function"){
                    err(result.data);
                }else{
                    layer.msg(result.msg);
                }
            }
        }
    });
}

// 弹出确认信息，并更新数据
function confirmUpdate(url , id , message){
   // alert(111);
    // 如果有数据
    if(message){
        layer.confirm(message , {btn: ['确认','取消']},function(){
            ajaxPost(url ,{id:id},function(){
                window.location.reload(true);
            });
        });
    }else{
        ajaxPost(url ,{id:id},function(){
            window.location.reload(true);
        });
    }
}

// 弹出确认信息，并更新数据
function confirmUpdates(url , ids , message){

    // 如果IDS为空
    if(ids == null || ids == ""){
        layer.msg("请选择数据！");
        return false;
    }

    // 如果有数据
    if(message){
        layer.confirm(message , {btn: ['确认','取消']},function(){
            ajaxPost(url ,{ids:ids},function(){
                window.location.reload(true);
            });
        });
    }else{
        ajaxPost(url ,{id:id},function(){
            window.location.reload(true);
        });
    }
}

// 下载数据
function downCsv(ids , message ,obj){

    // 如果IDS为空
    if(ids == null || ids == ""){
        layer.msg("请选择数据！");
        return false;
    }

    // 如果有数据
    if(message){
        layer.confirm(message , {btn: ['确认','取消']},function(){
            alert($("#" + obj));
            $("#" + obj).submit();
        });
    }else{
        $("#" + obj).submit();
    }
}

//弹出模态框
function modal(url, title, paraHeight, paraWidth)
{
    var width;
    if(typeof(paraWidth) == "undefined" || paraWidth == ""){
        width = "80%";
    }else{
        width = paraWidth;
    }

    var height;
    if(typeof(paraHeight) == "undefined" || paraHeight == ""){
        height = "80%";
    }else{
        height = paraHeight;
    }

    //打开弹出框
    layer.open({
        type: 2,
        title: title,
        shadeClose: true,
        shade: 0.8,
        area: [width, height],
        content: url
    });
}

// 打开URL
function openUrl(url){
    window.location.href = url;
}

// nice validator默认设置
$(function(){
    // 任何不可见的元素，都不作验证
    $('#form').validator({
        ignore: ':hidden',
        theme:'yellow_top',
        focusCleanup :true
    }).on('valid.form', function(e, form){
        layer.confirm("确认提交？" , {btn: ['确认','取消']},function(){
            ajaxPost($('#form').attr("action"),$('#form').serialize())
        });
    });

    $(".btn-submit").click(function(){
        $('#form').trigger("validate");
    });

    // 全选按钮点击
    $("input[type='checkbox'][name='allCheck']").click(function(){
        $("input[type='checkbox'][name='check']").prop("checked", $("input[name='allCheck']").prop("checked"));

        // 设置选中值
        setIds();
    });

    // 下面按钮点击
    $("input[type='checkbox'][name='check']").click(function(){
        // 如果按钮全选中
        if($("input[type='checkbox'][name='check']").length == $("input[type='checkbox'][name='check']:checked").length){
            $("input[name='allCheck']").prop("checked",true);
        }else{
            $("input[name='allCheck']").prop("checked",false);
        }

        // 设置选中值
        setIds();
    });
});

// 根据check的选中设置IDS的值
function setIds(){
    var ids = "0";
    $("input[type='checkbox'][name='check']:checked").each(function(){
        ids += "," + $(this).val();
    });

    if(ids == "0"){
        ids = "";
    }else{
        ids = ids.replace("0,","");
    }

    // 如果不为空
    if($("#ids")){
        $("#ids").val(ids);
    }
}

// 省级下拉框
function changeProvince(region){
    // 省级下拉框
    var $province = $("#provinceId").val();
    $("#" + region).val($province);

    // 如果没有选择值
    if($province == ""){
        $("#cityId").empty().append(new Option("",""));
        $("#areaId").empty().append(new Option("",""));
    }else{
        ajaxPost('/index.php/Admin/Tool/region',{parent_id:$province},function(data){
            $("#cityId").empty().append(new Option("",""));
            $("#areaId").empty().append(new Option("",""));

            // 列表
            for(var i=0;i<data.length;i++){
                var option ='<option  value="'+data[i]["id"] +'">'+data[i]["name"] +'</option>';
                $("#cityId").append(option);
            }
        });
    }
}

// 市级下拉框
function changeCity(region){
    var $city = $("#cityId").val();

    // 如果没有选择值
    if($city == ""){
        $("#" + region).val($("#provinceId").val());
        $("#areaId").empty().append(new Option("",""));
    }else{
        $("#" + region).val($city);
        ajaxPost('/index.php/Admin/Tool/region',{parent_id:$city},function(data){
            $("#areaId").empty().append(new Option("",""));
            console.log(data)
            // 列表
            for(var i=0;i<data.length;i++){
                if(data[i]['isdaili']==2){
                    var color='red';
                }else{
                    var color='black';
                }
                var option ='<option style="color:'+color +'" value="'+data[i]["id"] +'">'+data[i]["name"] +'</option>';
                $("#areaId").append(option);
            }
        })
    }
}

// 区级下拉框
function changeArea(region , area){
    var $area = $("#areaId").val();

    if($area == ""){
        $("#" + region).val($("#cityId").val());
    }else{
        $("#" + region).val($area);
    }
}