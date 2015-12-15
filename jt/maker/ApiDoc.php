<?php
/**
 * Created by PhpStorm.
 * User: zhbitcxy
 * Date: 2015/7/15
 * Time: 15:00
 * TODO 错误检测完善
 * TODOed 方法检查先检查有无router,如何无则不在检查?
 * TODOed 返回类型完善 int,float,string,bool,... attr:描述 => attr:type(默认空为string) 描述
 * TODOed 返回类型 在array基础上新增object,list类型
 * TODOed 返回类型，首个2tab，以后都1个tab
 * TODOed @param \jt\Requester $post 类型为$get $post 留空 $变量，前2个必须有参数, 类型作为下面编写参数的访问类型
 * TODOed 请求类型完善 attr:描述 => attr:type:value type2:value4 type3 type4:value4  (前面2个空格)描述
 * TODOed 请求类型any还没处理
 * TODO 修改全部文件更新，为更改后的文件才更新
 * TODO 全过程事务控制
 * TODOed 类名替换类名ID作为一个URL标识
 * xTODO 请求参数属性类型以表格形式展示
 * TODOed 请求参数表格中的访问方式换成是否必需列
 * TODO view:改善返回类型前端显示    9.25 version:1.0
 * TODO core:给类增加排序，给类注解增加@order num 序号越大排越前    9.25 version:1.0
 * TODOed core:给方法增加排序，其中方法为默认放置顺序    9.25 version:1.0
 * TODO core:给请求参数增加排序，其中方法为默认放置顺序    9.25 version:1.0
 * TODO core:给返回参数增加排序，其中方法为默认放置顺序    9.25 version:1.0
 * TODOed core,view:请求类型分三种path get post any需要分开显示    9.25 hit:只有三种？是。能否同时出现？是。 version:1.0
 * TODO core:重构解析方法函数    9.25 version:1.0
 * TODO view:url路径支持到方法导航级别 9.25 version:1.0
 * TODO view:增加方法导航左侧栏 9.25 version:1.0
 * TODO core,view:给类新增@group groupName,前端分组显示.分组注解 9.25 version:1.0
 *
 * TODO core:请求参数也增加objectList,object,list类型解析
 * TODO core:返回类型和请求object类型中，当键不是类型时是数据值时怎么表示？？？？其中，请求类型一定是object类型开始，因为请求参数一定是键值对,但是返回类型不一定
 * demo:
 * 获取最新的版本号
 * @description
 *    废弃特性:
 *        1.qweqwe
 *        2.123123
 *  新增特性:
 *        1.asda
 *        2.qweq
 * @param \jt\Requester $post/$get/$any/$variable 描述
 *    attr:type:value type2:value4 type3 type4:value4  描述
 *    attr:type:value type2:value4 type3 type4:value4  描述
 * @return type[int,float,bool,string,object,objectList,list,void(默认)] 描述1
 *    username:string 用户名
 *    age:int 年龄
 *  lock:bool 是否锁定
 *  profile:object 个人信息
 *  email:string 邮件
 *    phone:string 电话
 *    addressList:list 地址列表
 *  collectBag:objectList 收藏包
 *      id:int 包ID
 *      name:string 包名
 *      price:float 包价格
 *      attrs:objectList 包属性
 *        attrname:string 属性名
 *        attrvalue:string 属性值
 * @router get apk/version auth:public
 *
 * 参数覆盖规则:
 * 1.全局变量/局部变量后面定义的变量覆盖前面的变量,但是请求方法不同则作为不同变量
 * 2.全局get/post被局部get/post融合,相同参数以后者为准
 * 3.全局any能被局部get/post/any融合，相同参数以后者为准
 */
namespace jt\maker;

class ApiDoc extends Action{

}