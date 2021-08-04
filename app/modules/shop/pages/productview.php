<?php

namespace App\Modules\Shop\Pages;

use App\Application as App;
use App\Modules\Shop\Entity\Product;
use App\Modules\Shop\Entity\ProductComment;
use App\Modules\Shop\Helper;
use App\System;
use Zippy\Binding\PropertyBinding;
use Zippy\Html\DataList\ArrayDataSource;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\RedirectLink;

//детализация  по товару, отзывы
class ProductView extends Base
{

    public $msg, $attrlist, $clist;
    protected               $item_id;

    public function __construct($item_id = 0) {
        parent::__construct();

        $this->item_id = $item_id;
        $product = Product::load($item_id);
        if ($product == null) {
            App::Redirect404();
        }

        $options = \App\System::getOptions('shop');
        $this->_tvars['usefeedback'] = $options['usefeedback'] == 1;

        $this->add(new Label("breadcrumb", Helper::getBreadScrumbs($product->cat_id), true));
        $this->add(new ClickLink('backtolist', $this, 'OnBack'));

        $this->_title = $product->itemname;
        // $this->_description = $product->getDescription();

        $this->add(new \Zippy\Html\Link\BookmarkableLink('product_image'))->setValue("/loadshopimage.php?id={$product->image_id}");
        $this->product_image->setAttribute('href', "/loadshopimage.php?id={$product->image_id}");

        $this->add(new Label('productname', $product->itemname));
        $this->add(new Label('productcode', $product->item_code));
        $this->add(new Label('onstore'));
        $this->add(new Label('action'))->setVisible(false);
        $this->add(new \Zippy\Html\Label('manufacturername', $product->manufacturer))->SetVisible(strlen($product->manufacturer) > 0);

        $this->add(new Label('price', $product->getPrice($options['defpricetype']) . ' ' . $options['currencyname']));
        $this->add(new Label('actionprice', $product->productdata->actionprice . ' ' . $options['currencyname']))->setVisible(false);
        if ($product->productdata->actionprice > 0) {
            $this->price->setAttribute('style', 'text-decoration:line-through');
            $this->actionprice->setVisible(true);
            $this->action->setVisible(true);
        }

        $this->add(new Label('description', $product->getDescription(), true));
        $this->add(new TextInput('rated'))->setText($product->getRating());
        $this->add(new Label('comments', \App\Helper::l("shopfeedbaks", $product->comments)));

        $list = Helper::getAttributeValuesByProduct($product, false);
        $this->add(new \Zippy\Html\DataList\DataView('attributelist', new \Zippy\Html\DataList\ArrayDataSource($list), $this, 'OnAddAttributeRow'))->Reload();
        $this->add(new ClickLink('buy', $this, 'OnBuy'));
        $this->add(new ClickLink('addtocompare', $this, 'OnAddCompare'));
        $this->add(new RedirectLink('compare', "\\App\\Modules\\Shop\\Pages\\Compare"))->setVisible(false);

        $form = $this->add(new \Zippy\Html\Form\Form('formcomment'));
        $form->onSubmit($this, 'OnComment');
        $form->add(new TextInput('nick'));
        $form->add(new TextInput('rating'));
        $form->add(new TextArea('comment'));
        $form->add(new TextInput('capchacode'));
        $form->add(new \ZCL\Captcha\Captcha('capcha'));

        $this->clist = ProductComment::findByProduct($product->item_id);
        $this->add(new \Zippy\Html\DataList\DataView('commentlist', new \Zippy\Html\DataList\ArrayDataSource(new PropertyBinding($this, 'clist')), $this, 'OnAddCommentRow'));
        $this->commentlist->setPageSize(10);
        $this->add(new \Zippy\Html\DataList\Pager("pag", $this->commentlist));
        $this->commentlist->Reload();

        if ($product->disabled == 1 || $product->noshop == 1) {
            $this->onstore = \App\Helper::l('cancelsell');
            $this->buy->setVisible(false);
        } else {

            if ($product->getQuantity($options['defstore']) > 0) {
                $this->onstore->setText(\App\Helper::l('isonstore'));
                $this->buy->setValue(\App\Helper::l('tobay'));
            } else {
                $this->onstore->setText(\App\Helper::l('fororder'));
                $this->buy->setValue(\App\Helper::l('toorder'));
            }
        }

        $imglist = array();

        foreach ($product->getImages(true) as $id) {
            $imglist[] = \App\Entity\Image::load($id);
        }
        $this->add(new DataView('imagelist', new ArrayDataSource($imglist), $this, 'imglistOnRow'))->Reload();
        $this->_tvars['islistimage'] = count($imglist) > 1;

        $recently = \App\Session::getSession()->recently;
        if (!is_array($recently)) {
            $recently = array();
        }
        $recently[$product->item_id] = $product->item_id;
        \App\Session::getSession()->recently = $recently;
    }

    public function OnBack($sender) {
        $product = Product::load($this->item_id);

        App::Redirect("\\App\\Modules\\Shop\\Pages\\Catalog", $product->cat_id);
    }

    public function OnAddAttributeRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        $datarow->add(new Label("attrname", $item->attributename));
        $meashure = "";
        $nodata = \App\Helper::l("shopattrnodata");
        $yes = \App\Helper::l("shopattryes");
        $no = \App\Helper::l("shopattrno");
        $value = $item->attributevalue;
        if ($item->attributetype == 2) {
            $meashure = $item->valueslist;
        }

        if ($item->attributetype == 1) {
            if ($item->attributevalue == '0') {
                $value = $no;
            }
            if ($item->attributevalue == '1') {
                $value = $yes;
            }
        }
        $value = $value . $meashure;
        if ($item->hasData() == false) {
            $value = $nodata;
        }
        $datarow->add(new Label("attrvalue", $value));
    }

    //добавление в корзину
    public function OnBuy($sender) {
        $product = Product::load($this->item_id);
        $product->quantity = 1;
        \App\Modules\Shop\Basket::getBasket()->addProduct($product);
        $this->setSuccess("addedtocart");
        $this->resetURL();
        //  App::RedirectURI('/pcat/' . $product->cat_id);
    }

    //добавить к форме сравнения
    public function OnAddCompare($sender) {
        $product = Product::load($this->item_id);
        $comparelist = \App\Modules\Shop\CompareList::getCompareList();
        if (false == $comparelist->addProduct($product)) {

            $this->setWarn('onlythesamecategory');
            return;
        }
        // App::RedirectURI('/pcat/'.$product->group_id)  ;
    }

    //добавать комментарий 
    public function OnComment($sender) {

        $entercode = $this->formcomment->capchacode->getText();
        $capchacode = $this->formcomment->capcha->getCode();
        if (strlen($entercode) == 0 || $entercode != $capchacode) {
            $this->setError("invalidcapcha");

            return;
        }

        $comment = new \App\Modules\Shop\Entity\ProductComment();
        $comment->item_id = $this->item_id;
        $comment->author = $this->formcomment->nick->getText();
        $comment->comment = $this->formcomment->comment->getText();
        $comment->rating = $this->formcomment->rating->getText();
        $comment->created = time();
        $comment->Save();
        $this->formcomment->nick->setText('');
        $this->formcomment->comment->setText('');
        $this->formcomment->rating->setText('0');
        $this->clist = ProductComment::findByProduct($this->item_id);
        $this->commentlist->Reload();

        $this->updateComments();
    }

    protected function beforeRender() {
        parent::beforeRender();

        if (\App\Modules\Shop\CompareList::getCompareList()->hasProsuct($this->item_id)) {
            $this->compare->setVisible(true);
            $this->addtocompare->setVisible(false);
        } else {
            $this->compare->setVisible(false);
            $this->addtocompare->setVisible(true);
        }
    }

    public function OnAddCommentRow(\Zippy\Html\DataList\DataRow $datarow) {
        $item = $datarow->getDataItem();
        if ($item->moderated == 1) {
            $item->comment = "Отменено  модератором";
        }
        $datarow->add(new Label("nick", $item->author));
        $datarow->add(new Label("comment", $item->comment));
        $datarow->add(new Label("created", \App\Helper::fdt($item->created)));
        $datarow->add(new TextInput("rate"))->setText($item->rating);
        $datarow->add(new ClickLink('deletecomment', $this, 'OnDeleteComment'))->setVisible(System::getUser()->userlogin == 'admin' && $item->moderated != 1);
    }

    //удалить коментарий
    public function OnDeleteComment($sender) {
        $comment = $sender->owner->getDataItem();
        $comment->moderated = 1;
        $comment->rating = 0;
        $comment->save();
        // App::$app->getResponse()->addJavaScript("window.location='#{$comment->comment_id}'", true);
        //\Application::getApplication()->Redirect('\\ZippyCMS\\Modules\\Articles\\Pages\\ArticleList');
        $this->clist = ProductComment::findByProduct($this->item_id);
        $this->commentlist->Reload();
        $this->updateComments();
    }

    private function updateComments() {
        $conn = \ZDB\DB::getConnect();

        $product = Product::load($this->item_id);

        $product->rating = $conn->GetOne("select sum(rating)/count(*) from `shop_prod_comments`where  item_id ={$this->item_id} and moderated <> 1 and  rating >0");
        $product->rating = round($product->rating);
        $product->comments = $conn->GetOne("select count(*) from `shop_prod_comments`where  item_id ={$this->item_id} and moderated <> 1");
        $product->save();
        $this->rated->setText($product->rating);
        $this->comments->setText("Отзывов({$product->comments})");
    }

    public function imglistOnRow($row) {
        $image = $row->getDataItem();

        $row->add(new \Zippy\Html\Link\BookmarkableLink('product_thumb'))->setValue("/loadshopimage.php?id={$image->image_id}&t=t");
        $row->product_thumb->setAttribute('href', "/loadshopimage.php?id={$image->image_id}");
    }

}
