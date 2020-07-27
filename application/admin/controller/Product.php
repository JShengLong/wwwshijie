<?php

namespace app\admin\controller;

use HXC\Admin\Common;
use HXC\Admin\curd;
use HXC\Admin\curdInterface;

class Product extends Right implements curdInterface
{
    /**
     * 特别说明
     * Common中的文件不能直接修改！！！！
     * 如果需要进行业务追加操作，请复制Common中的对应的钩子方法到此控制器中后在进行操作
     * Happy Coding
     **/
    use curd, Common;

    protected $modelName = 'Product';  //模型名,用于add和update方法
    protected $indexField = ['p_weight', 'p_storage_mode', 'category1', 'id', 'p_name', 'p_img', 'p_isHot', 'p_imgs', 'p_oldprice', 'p_introduction', 'p_html', 'p_isDelete', 'p_isUp', 'p_isHot', 'p_createTime', 'p_updateTime', 'p_stock', 'p_sales', 'p_category_id', 'p_integral', 'p_sort'];  //查，字段名
    protected $addField = ['p_leibie', 'p_isjinkou', 'p_yuanchan', 'p_codes', 'p_shiyan', 'p_garde', 'p_shengchan', 'p_shoumai', 'p_techan', 'p_huohao', 'p_baozhuang', 'p_weight', 'p_storage_mode', 'p_onenum', 'p_oneprice', 'p_twonum', 'p_twoprice', 'p_threenum', 'p_threeprice', 'category1', 'id', 'p_name', 'p_img', 'p_video', 'p_imgs', 'p_oldprice', 'p_introduction', 'p_html', 'p_isDelete', 'p_isUp', 'p_isHot', 'p_createTime', 'p_updateTime', 'p_stock', 'p_sales', 'p_category_id', 'p_integral', 'p_place_of_origin', 'p_brand', 'p_name_of_factory', 'p_site', 'p_ingredient_list', 'p_phone', 'p_shelf_life', 'p_production_license', 'p_phone'];    //增，字段名
    protected $editField = ['p_brand','p_leibie', 'p_isjinkou', 'p_yuanchan', 'p_codes', 'p_shiyan', 'p_garde', 'p_shengchan', 'p_shoumai', 'p_techan', 'p_huohao', 'p_baozhuang', 'p_weight', 'p_storage_mode', 'p_onenum', 'p_oneprice', 'p_twonum', 'p_twoprice', 'p_threenum', 'p_threeprice', 'category1', 'id', 'p_name', 'p_img', 'p_video', 'p_imgs', 'p_oldprice', 'p_introduction', 'p_html', 'p_isDelete', 'p_isUp', 'p_isHot', 'p_createTime', 'p_updateTime', 'p_stock', 'p_sales', 'p_category_id', 'p_integral', 'p_place_of_origin', 'p_brand', 'p_name_of_factory', 'p_site', 'p_ingredient_list', 'p_phone', 'p_shelf_life', 'p_production_license', 'p_phone'];   //改，字段名
    /**
     * 条件查询，字段名,例如：无关联查询['name','age']，关联查询['name','age',['productId' => 'product.name']],解释：参数名为productId,关联表字段p.name
     * 默认的类型为输入框，如果有下拉框，时间选择等需求可以这样定义['name',['type' => ['type','select']]];目前有select,time_start,time_end三种可被定义
     * 通过模型定义的关联查询，可以这样定义['name',['memberId'=>['member.nickname','relation']]],只能有一个关联方法被定义为relation，且字段前的表别名必须为关联的方法名
     * @var array
     */
    protected $cache = false; //是否开启缓存查询，仅对后台查询生效，通过模型方式进行增，改，删的操作，都会刷新缓存
    protected $searchField = ['p_storage_mode', 'category1', 'id', 'p_name', 'p_isHot', 'p_isUp', 'p_category_id'];
    protected $orderField = 'id desc';  //排序字段
    protected $pageLimit = 10;               //分页数
    protected $addTransaction = false;        //添加事务是否开启，开启事务证明你需要在addEnd方法里追加业务逻辑
    protected $editTransaction = false;       //编辑事务是否开启，开启事务证明你需要在editEnd方法里追加业务逻辑
    protected $deleteTransaction = false;     //删除事务是否开启，开启事务证明你需要在deleteEnd方法里追加业务逻辑

    //增，数据检测规则
    protected $add_rule
        = [
            //'nickName|昵称'  => 'require|max:25'
            'p_name|商品名称'         => 'require',
            'p_img|商品图片'          => 'require',
            'p_imgs|商品图集'         => 'require',
            'p_oldprice|价格'       => 'require',
            'p_html|详情页'          => 'require',
            'p_category_id|类型'    => 'require',
            'p_storage_mode|储藏类型' => 'require',
            'p_isUp|是否上架'         => 'require',

        ];
    //改，数据检测规则
    protected $edit_rule
        = [
            //'nickName|昵称'  => 'require|max:25'
            'p_name|商品名称'         => 'require',
            'p_img|商品图片'          => 'require',
            'p_imgs|商品图集'         => 'require',
            'p_oldprice|价格'       => 'require',
            'p_html|详情页'          => 'require',
            'p_category_id|类型'    => 'require',
            'p_storage_mode|储藏类型' => 'require',
//            'p_isUp|是否上架'         => 'require',
//        'p_stock|库存'  => 'require',

        ];
    protected $p_storage_mode = ['' => '', 0 => '', 1 => '常温', 2 => '冷藏', 3 => '冷冻'];

    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function indexAssign($data)
    {

        $data['lists'] = [
            'hxc'            => [],
            'category1'      => $this->getCategoryList(),
            'p_isUp'         => getDropdownList('isUp'),
            'p_isHot'        => getDropdownList('isHot'),
            'p_storage_mode' => $this->p_storage_mode

        ];
        return $data;
    }

    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function addAssign($data)
    {
        $data['lists'] = [
            'hxc'            => [],
            'p_category_id'  => $this->getCategoryList(),
            'category1'      => $this->getCategoryLists1(),
            'category2'      => [],
            'p_isUp'         => getDropdownList('isUp'),
            'p_storage_mode' => $this->p_storage_mode

        ];
        return $data;
    }

    /**
     * 输出到列表视图的数据捕获
     * @param $data
     * @return mixed
     */
    public function editAssign($data)
    {
        $data['data']['p_imgs_arr'] = explode(',', $data['data']['p_imgs']);

        $data['data']['category1'] = db('category')->where(['id' => $data['data']['p_category_id']])->value('pid');
        $list      = array("" => "");
        $categorys = db('category')
            ->where(['pid' => $data['data']['category1']])
            ->select();

        for ($j = 0; $j < count($categorys); $j++) {
            $category   = $categorys[$j];
            $key        = $category['id'];
            $list[$key] = $category['name'];
        }

//        $data['data']['category1'] = 15;
        $data['lists']             = [
            'hxc'            => [],
            'p_category_id'  => $this->getCategoryList(),
            'category1'      => $this->getCategoryLists1(),
            'category2'      => $list,
            'p_storage_mode' => $this->p_storage_mode
        ];
        return $data;
    }

    public function pageEach($item, $key)
    {
        $item->cate_name  = db('category')
            ->where(['id' => $item->category1])
            ->value('name');
        $item->cate_name1 = db('category')
            ->where(['id' => $item->p_category_id])
            ->value('name');
        return $item;
    }

    /**
     * 修改指定字段
     *
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * @author: Gavin
     * @time: 2019/9/19 16:52
     */
    public function change_status()
    {
        if (request()->isPost()) {
            $data        = input('post.');
            $id          = $data['id'][0];
            $field       = $data['id'][1];
            $val         = $data['id'][2];
            $map[$field] = $val;
            $res         = db($this->modelName)->where(['id' => $id])->update($map);
            if ($res) {
                return json_suc();
            } else {
                return json_err();
            }

        }
    }


    /**
     * 新增数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function addData($data)
    {
        if (empty($data['p_imgs'])) {
            return json_err(-1, '请上传图集');
        }
        if($data['p_video']){
            $data['p_video_img']=$data['p_video'].'?x-oss-process=video/snapshot,t_1000,m_fast';
        }
        $data['p_imgs']       = implode(',', $data['p_imgs']);
        $data['p_createTime'] = now_datetime();
        $data['p_updateTime'] = now_datetime();
        return $data;
    }


    /**
     * 新增数据插入数据库前数据捕获（注意：在数据验证之前）
     * @param $data
     * @return mixed
     */
    public function editData($data)
    {
        if (is_array($data['p_imgs'])) {
            $data['p_imgs'] = implode(',', $data['p_imgs']);
        }
        if($data['p_video']){
            $data['p_video_img']=$data['p_video'].'??x-oss-process=video/snapshot,t_1000,m_fast';
        }
        $data['p_updateTime'] = now_datetime();
        return $data;
    }

    /**
     * 商品详情页
     *
     * @author: Gavin
     * @time: 2019/9/21 9:59
     */
    public function product_detail()
    {
        $id     = input('id');
        $detail = db('product')
            ->where(['id' => $id])
            ->value('p_html');
        $this->assign('detail', $detail);
        return view();
    }

    /**
     * 添加sku页面
     * @return \think\response\View
     */
    public function sku_index()
    {
        $item_id = input('id');
        $this->assign('item_id', $item_id);
        return view();
    }

    /**
     * sku编辑页面
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sku_edit()
    {
        $item_id = input('id');
        $data    = db('fa_item_attr_key')
            ->where(['item_id' => $item_id])
            ->select();
        $need    = [];
        foreach ($data as $key => $item) {
            $need[$key]                = $item;
            $need[$key]['itemattrval'] = db('fa_item_attr_val')
                ->where(['attr_key_id' => $item['attr_key_id']])
                ->select();
        }
        $sku  = db('fa_item_sku')->where(['item_id' => $item_id])->select();
        $skus = [];
        foreach ($sku as $item) {
            $skus[] = $item;
        }
        $this->view->assign('itemAttr', $need);
        $this->view->assign('itemSku', json_encode($skus, 320));
        $this->assign('item_id', $item_id);
        return $this->view->fetch();
    }

    /**
     * 设置sku
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function save_attr()
    {
        if (request()->isPost()) {
            $data    = request()->post();
            $key     = json_decode($data['key'], true);
            $value   = json_decode($data['value'], true);
            $item_id = $data['item_id'];
            $key_id  = [];
//            ItemAttrKey::where(['item_id'=>$item_id])->delete();
            db('fa_item_attr_key')
                ->where(['item_id' => $item_id])
                ->update(['item_id' => '']);
            foreach ($key as $k) {
                $attr_key = db('fa_item_attr_key')
                    ->where(['attr_name' => $k, 'item_id' => $item_id])
                    ->find();
                if (!$attr_key) {
                    $saver['attr_name'] = $k;
                    $saver['item_id']   = $item_id;
                    $attr_key_id        = db('fa_item_attr_key')->insertGetId($saver);
                }
                $key_id[] = $attr_key_id;
            }
            $tm_v_in = [];
            $tm_v    = [];
            db('fa_item_attr_val')
                ->where(['item_id' => $item_id])
                ->update(['item_id' => '']);
            foreach ($value as $key => $v) {
                $attr_key_id = $key_id[$key];
                foreach ($v as $v1) {
                    $attr_value
                        = db('fa_item_attr_val')
                        ->where(['attr_value' => $v1, 'attr_key_id' => $attr_key_id])
                        ->find();
                    if (!$attr_value) {
                        $saver_value['attr_key_id'] = $attr_key_id;
                        $saver_value['attr_value']  = $v1;
                        $saver_value['item_id']     = $item_id;
                        $symbol                     = db('fa_item_attr_val')
                            ->insertGetId($saver_value);
                    }
                    $tm_v[] = $symbol;

                }
//
            }
            return json_suc(1, '请求成功', ['key' => $key_id, 'value' => $tm_v]);

        }
        return json_suc(1, '请求成功');
    }

    /**
     * 保存商品规格价格
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save_sku()
    {
        if (request()->isPost()) {
            $data = request()->post();
            db('fa_item_sku')
                ->where(['item_id' => $data[0]['item_id']])
                ->update(['item_id' => '']);
            $stock = 0;
            foreach ($data as $item) {
                $aa = db('fa_item_sku')
                    ->where(['attr_symbol_path' => $item['symbol']])
                    ->find();
                if ($aa) {
                    $sku['original_price'] = $item['original_price'];
                    $sku['price']          = $item['price'];
                    $sku['stock']          = $item['stock'];
                    $sku['item_id']        = $item['item_id'];
                    db('fa_item_sku')
                        ->where([
                                    'sku_id' => $aa['sku_id']
                                ])
                        ->update($sku);
                } else {
                    $sku['item_id']          = $item['item_id'];
                    $sku['original_price']   = $item['original_price'];
                    $sku['price']            = $item['price'];
                    $sku['stock']            = $item['stock'];
                    $stock                   = $stock + $item['stock'];
                    $sku['attr_symbol_path'] = $item['symbol'];
                    db('fa_item_sku')->insert($sku);
                }
            }
            db('product')
                ->where(['id' => $data[0]['item_id']])
                ->update(['p_stock' => $stock]);
            return json_suc();
        }
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function uploadImg()
    {
        $file = request()->file('file');
        if ($file) {
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠
            $info = $file
                ->validate(['size' => 10 * 1024 * 1024, 'ext' => 'jpg,png,gif,jpeg'])
                ->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                $url = "/uploads/" . $info->getSaveName();
                if ($url) {
                    return json_suc(1, '上传成功', ['url' => saver() . $url, 'img' => $url]);
                } else {
                    return json_err(0, "上传失败");
                }
            } else {
                // 上传失败获取错误信息
                return json_err(0, $file->getError());
            }
        }
    }

    /**
     * 更改排序
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateSort()
    {
        $id   = input('id');
        $sort = input('text');
        db('product')->where('id', $id)->update(['p_sort' => $sort]);
        return json_suc();
    }

    /**
     * 分类联动
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function ld()
    {
        $pid = input('pid');
        if (empty($pid)) {
            echo $pid;
            die;
        }
        $list = db('category')->where(['pid' => $pid])->select();
        $a    = "";
        for ($j = 0; $j < count($list); $j++) {
            $category = $list[$j];
            $key      = $category['id'];
            $name     = $category['name'];
            $a        .= '<option value="' . $key . '">' . $name . '</option>';
        }
        echo $a;
    }

    /**
     *批量上架
     * @return \think\response\Json
     */
    public function up()
    {
        $data = input('data');
        $data = implode(',', json_decode($data, true));
        db('product')->where('id', 'in', $data)->setField('p_isUp', 2);
        return json_suc();
    }

    /**
     * 批量下架
     * @return \think\response\Json
     */
    public function unp()
    {
        $data = input('data');
        $data = implode(',', json_decode($data, true));
        db('product')->where('id', 'in', $data)->setField('p_isUp', 1);
        return json_suc();
    }

    public function ladder()
    {
        $id   = input('id');
        $list = db('fa_item_sku')
            ->where('item_id', $id)
            ->select();

        foreach ($list as $key => $value) {
            $list[$key]['name']   = $this->sku($value['sku_id']);
            $list[$key]['ladder'] = db('ladder')
                ->where(['sku_id' => $value['sku_id']])
                ->order('sort asc')
                ->select();
            $fa_item_attr_val     = db('fa_item_attr_val')
                ->where(['symbol' => $value['attr_symbol_path']])
                ->find();
            $list[$key]['g_name'] = db('fa_item_attr_key')
                ->where(['attr_key_id' => $fa_item_attr_val['attr_key_id']])
                ->value('attr_name');
        }
        $this->assign('list', $list);
        $this->assign('id', $id);
        return view();
    }

    /**
     * 阶梯价格列表
     * @return \think\response\View
     */
    public function ladder_index()
    {
        //规格id
        $id = input('id');
        //阶梯价格列表
        $list = db('ladder')
            ->where(['sku_id' => $id])
            ->order('sort asc')
            ->select();
        $this->assign('list', $list);
        $this->assign('id', $id);
        return view();
    }

    /**
     * 添加阶梯价格
     * @return \think\response\Json|\think\response\View
     */
    public function ladder_add()
    {
        if (request()->isPost()) {

            $id     = input('post.id');
            $num    = input('post.num');
            $result = input('post.result');


            $result = explode(',', $result);
            $result = array_reverse($result);
            unset($result[0]);
            $result = array_reverse($result);
            $arr    = [];
            $aa     = 1;

            foreach ($result as $key => $value) {
                if ($num % 3 == 0) {
                    $arr[$aa]['num'] = $value;
                } elseif ($num % 3 == 2) {
                    $arr[$aa]['price'] = $value;
                } elseif ($num % 3 == 1) {
                    $arr[$aa]['sort'] = $value;
                    $aa++;
                }
                $num--;
            }
            $res = [1];
            foreach ($arr as $key => $value) {
                $add   = [
                    'sku_id'     => $id,
                    'num'        => $value['num'],
                    'price'      => $value['price'],
                    'sort'       => $value['sort'],
                    'createTime' => now_datetime(),
                    'updateTime' => now_datetime(),
                ];
                $res[] = db('ladder')->insert($add);
            }
            if (in_array(0, $res)) {
                return json_err();
            }
            return json_suc();
        } else {
            $id   = input('id');
            $list = db('ladder')->where(['sku_id' => $id])->select();
            $keys = db('ladder')->where(['sku_id' => $id])->count();
            $this->assign('list', $list);
            $this->assign('keys', $keys);
            $this->assign('hid', $id);
            return view();
        }
    }

    public function ladder_edit()
    {
        if (request()->isPost()) {
            $data = request()->param();
            $id   = input('post.id');
            $add  = [
                'num'        => $data['num'],
                'price'      => $data['price'],
                'sort'       => $data['sort'],
                'updateTime' => now_datetime(),
            ];
            $res  = db('ladder')->where(['id' => $id])->update($add);
            if ($res == false) {
                return json_err();
            }
            return json_suc();
        } else {
            //阶梯价格id
            $id   = input('id');
            $data = db('ladder')->where(['id' => $id])->find();
            $this->assign('id', $id);
            $this->assign('data', $data);
            return view();
        }
    }

    /**
     * 删除阶梯价格
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function ladder_delete()
    {
        $id  = input('id');
        $res = db('ladder')->where(['id' => $id])->delete();
        if ($res == false) {
            return json_err();
        }
        return json_suc();
    }

    /**
     * 组合sku参数
     * @param $id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sku($id)
    {
        $sku = db('fa_item_sku')->where(['sku_id' => $id])->find();
        //组合sku
        $attr_symbol_path = explode(',', $sku['attr_symbol_path']);
        $sku_name         = [];
        foreach ($attr_symbol_path as $k => $v) {
            $sku_name[$k] = db('fa_item_attr_val')
                ->where(['symbol' => $v])
                ->value('attr_value');
        }
        return implode("/", $sku_name) . ";";
    }

    public function sku_add()
    {
        if (request()->isPost()) {
            $param            = request()->param();
            $fa_item_attr_key = db('fa_item_attr_key')
                ->where(['item_id' => $param['id']])
                ->find();
            db()->startTrans();
            if (empty($fa_item_attr_key)) {
                $add = [
                    'item_id'   => $param['id'],
                    'attr_name' => $param['name'],
                ];
                $aid = db('fa_item_attr_key')->insertGetId($add);
                if ($aid == false) {
                    db()->rollback();
                    return json_err();
                }
            } else {
                $aid = $fa_item_attr_key['attr_key_id'];
            }
            $fa_item_attr_val = db('fa_item_attr_val')
                ->where(['attr_key_id' => $aid, 'item_id' => $param['id'], 'attr_value' => $param['canshu']])
                ->find();
            if ($fa_item_attr_val) {
                db()->rollback();
                return json_err(-1, '规格名称不能一样');
            }
            $add1 = [
                'attr_key_id' => $aid,
                'item_id'     => $param['id'],
                'attr_value'  => $param['canshu'],
            ];
            $bid  = db('fa_item_attr_val')->insertGetId($add1);
            if ($bid == false) {
                db()->rollback();
                return json_err();
            }

            $add2 = [
                'item_id'          => $param['id'],
                'attr_symbol_path' => $bid,
                'price'            => $param['price'],
                'original_price'   => $param['weight'],
                'stock'            => $param['stock'],
            ];
            $res  = db('fa_item_sku')->insert($add2);
            if ($res == false) {
                db()->rollback();
                return json_err();
            }
            db()->commit();
            return json_suc();
        } else {
            $id               = input('id');
            $fa_item_attr_key = db('fa_item_attr_key')
                ->where(['item_id' => $id])
                ->find();
            if ($fa_item_attr_key) {
                $data['name'] = $fa_item_attr_key['attr_name'];
            } else {
                $data['name'] = '';
            }
            $this->assign('data', $data);
            $this->assign('id', $id);
            return view();
        }
    }

    public function skuedit()
    {
        if (request()->isPost()) {
            $param = request()->param();

            db()->startTrans();
            $fa_item_sku = db('fa_item_sku')
                ->where(['sku_id' => $param['id']])
                ->find();
            $res         = db('fa_item_attr_val')
                ->where(['symbol' => $fa_item_sku['attr_symbol_path']])
                ->setField('attr_value', $param['canshu']);
            if ($res === false) {
                db()->rollback();
                return json_err();
            }
            $save = [
                'price'          => $param['price'],
                'original_price' => $param['weight'],
                'stock'          => $param['stock'],
            ];
            $res  = db('fa_item_sku')->where(['sku_id' => $param['id']])
                                     ->update($save);
            if ($res === false) {
                db()->rollback();
                return json_err();
            }
            db()->commit();
            return json_suc();
        } else {
            $id                    = input('id');
            $fa_item_sku           = db('fa_item_sku')
                ->where(['sku_id' => $id])
                ->find();
            $fa_item_attr_val      = db('fa_item_attr_val')
                ->where(['symbol' => $fa_item_sku['attr_symbol_path']])
                ->find();
            $fa_item_sku['canshu'] = $fa_item_attr_val['attr_value'];
            $fa_item_attr_key      = db('fa_item_attr_key')
                ->where(['attr_key_id' => $fa_item_attr_val['attr_key_id']])
                ->find();
            $fa_item_sku['name']   = $fa_item_attr_key['attr_name'];
            $this->assign('data', $fa_item_sku);
            return view();
        }
    }

    public function skudelete()
    {
        $id = input('id');

        $fa_item_sku=db('fa_item_sku')
            ->where(['sku_id'=>$id])
            ->find();
        $fa_item_attr_val=db('fa_item_attr_val')
            ->where(['symbol'=>$fa_item_sku['attr_symbol_path']])
            ->find();
        db()->startTrans();
        $res=db('fa_item_sku')
            ->where(['sku_id'=>$id])
            ->delete();
        if($res==false){
            db()->rollback();
            return json_err();
        }
        $res1=db('fa_item_attr_val')
            ->where(['symbol'=>$fa_item_sku['attr_symbol_path']])
            ->delete();
        if($res1==false){
            db()->rollback();
            return json_err();
        }
        $fa_item_skus=db('fa_item_sku')
            ->where(['item_id'=>$fa_item_sku['item_id']])
            ->select();
        if(empty($fa_item_skus)){
            $res2=db('fa_item_attr_key')
                ->where(['attr_key_id'=>$fa_item_attr_val['attr_key_id']])
                ->delete();
            if($res2==false){
                db()->rollback();
                return json_err();
            }
        }
        db()->commit();
        return json_suc();
    }
}