<?php
/**
 * @filesource modules/personnel/controllers/write.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Personnel\Write;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=personnel-write.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ฟอร์มสร้าง/แก้ไข บุคลากร.
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Edit} {LNG_Personnel}');
        // เลือกเมนู
        $this->menu = 'settings';
        // อ่านข้อมูลรายการที่เลือก
        $index = \Personnel\Write\Model::get($request->request('id')->toInt());
        // member
        if ($index && $login = Login::isMember()) {
            // ตัวเอง หรือสามารถจัดการรายชื่อบุคลากรได้
            if ($login['id'] == $index->id || Login::checkPermission($login, 'can_manage_personnel')) {
                // แสดงผล
                $section = Html::create('section', array(
                    'class' => 'content_bg',
                ));
                // breadcrumbs
                $breadcrumbs = $section->add('div', array(
                    'class' => 'breadcrumbs',
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-customer">{LNG_Settings}</span></li>');
                $ul->appendChild('<li><span>{LNG_Personnel}</span></li>');
                $ul->appendChild('<li><a href="{BACKURL?module=personnel-write&id=0}">{LNG_Personnel list}</a></li>');
                $ul->appendChild('<li><span>{LNG_Edit}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-category">'.$this->title.'</h2>',
                ));
                // แสดงฟอร์ม
                $section->appendChild(createClass('Personnel\Write\View')->render($index));

                return $section->render();
            }
        }
        // 404

        return \Index\Error\Controller::execute($this);
    }
}
