<?php
/**
 * -------------------------------------------------------------------------
 * Oitpriority plugin for GLPI
 * Copyright (C) 2021 by the Belwest, Kapeshko Oleg.
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Oitpriority.
 *
 * Oitpriority is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Oitpriority is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Oitpriority. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

class PluginOitpriorityCalculation extends CommonDBTM {

    /**
     * Modify default values of fields of ticket form
     *
     * @param array $params [item, options]
     */
    static function post_item_form($params) {
        $item       = $params['item'];
        $options    = $params['options'];
        if ($item->getType() == 'Ticket') {
            // для новой заявки
            if (!($item->getID())) {
                // выбрана ли категория и входит ли пользователь в группу ОИТ
                if ($options['itilcategories_id'] > 0 && !(Group_User::isUserInGroup(Session::getLoginUserID(), 5))) {
                    $calc = new self;
                    $calc->modifyPriority($options['itilcategories_id']);
                } else {
                    //TODO "НЕ ОИТ";
                }
            // для существующей заявки
            } else {
                // добавление слушателя события onchange для select выбора категорий
                echo "
                    <script>
                        // нечувствительность к регистру для :contains
                        jQuery.expr[':'].Contains = jQuery.expr.createPseudo(function(arg) {
                            return function( elem ) {
                                return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
                            };
                        });

                        let th = $('tr').find('th:Contains(\"приоритет\")');
                        let tr = $(th).closest('tr');
                        let td = $(th).next('td');
                        $('select[id*=\"dropdown_itilcategories_id\"]').on('change', function(e) {
                            $('#msg_priority').remove();
                            if ($('span[id*=\"select2-dropdown_itilcategories_id\"]').text().search('Отдел информационных технологий') >= 0) {
                                $(th).text('Приоритет (срочность)');
                                $('#new_priority').remove();
                                $(td).html('".CommonITILObject::getPriorityName(2)."');
                                $(tr).before(function() {
                                    return '<tr id=\'msg_priority\'><th colspan=\'4\' style=\'color: red; font-size: larger; font-weight: bold;\'>Приоритет заявки для ОИТ по умолчанию НИЗКИЙ! Его можно изменить после смены категории!</th><th></th></tr>';
                                });
                            }
                        });
                    </script>
                ";

                // изменение интерфейса заявки
                $ticket = new Ticket;
                $ticket->getFromDB($item->getID());
                if ($ticket->fields['itilcategories_id'] > 0) {
                    $priority = new self();
                    $priority->getFromDB($ticket->fields['id']);
                    if (!($priority->isNewItem())) {
                        $calc = new self;
                        $calc->modifyPriority($ticket->fields['itilcategories_id'], $ticket->fields['priority'], $ticket->fields['status'], $priority->fields['assign_date'], $priority->fields['expire_date'], $priority->fields['status']);
                    }
                }
            }
        } else {

        }
    }
  
    /**
     * Add new OITPriority item
     *
     * @param Ticket $item
     */
    static function item_add_priority_time(Ticket $item) {
        // входит ли пользователь в группу ОИТ
        if (!(Group_User::isUserInGroup(Session::getLoginUserID(), 5))) {
            $category = new ITILCategory;
            $category->getFromDB($item->input['itilcategories_id']);
            // входит ли категория в раздел ОИТ
            if ($category->fields['itilcategories_id'] == 133 || $category->fields['id'] == 133) {
                // если приоритет не установлен, то устанваливается в зависмости от категории
                $item->input['priority'] = in_array($category->fields['id'], [439, 440, 441]) ? 4 : 2;
                // добавление новой записи в БД
                $ticket = new self;
                $ticket->fields['id'] = $item->fields['id'];
                $ticket->fields['assign_date'] = date('Y-m-d H:i:s');
                $ticket->fields['expire_date'] = $ticket->getExpireDate($ticket->fields['assign_date'], $item->input['priority']);
                $ticket->fields['status'] = 2;
                $ticket->addToDB();
            }
        }
    }

    /**
     * Update OITPriority item
     *
     * @param Ticket $item
     */
    static function item_update_priority_time(Ticket $item) {
        
        // инициализации объектов старой и новой категорий, объекта self
        $old_cat = new ITILCategory;
        $old_cat->getFromDB($item->fields['itilcategories_id']);
        $new_cat = new ITILCategory;
        $new_cat->getFromDB($item->input['itilcategories_id']);
        $ticket = new self;
        $ticket->getFromDB($item->fields['id']);

        // смена статуса записи на В РАБОТЕ
        if (!(Group_User::isUserInGroup(Session::getLoginUserID(), 5)) && $item->input['status'] < 5) {
            // входит ли категория в раздел ОИТ
            if ($new_cat->fields['itilcategories_id'] == 133 || $new_cat->fields['id'] == 133) {
                if ($ticket->isNewItem() && date('Y-m-d H:i:s') > date('2021-07-20 08:44:16')) {
                    self::item_add_priority_time($item);
                } else {
                    // если приоритет не установлен, то устанваливается в зависмости от категории
                    if (!(isset($item->input['priority']))) {
                        $item->input['priority'] = in_array($new_cat->fields['id'], [439, 440, 441]) ? 4 : 2;
                    }
                    // обновление записи при смене приоритета
                    if ($item->fields['priority'] != $item->input['priority']) {
                        $ticket->fields['assign_date'] = date('Y-m-d H:i:s');
                        $ticket->fields['expire_date'] = $ticket->getExpireDate($ticket->fields['assign_date'], $item->input['priority']);
                        $ticket->fields['status'] = 2;
                        $ticket->updateInDB(array('assign_date', 'expire_date', 'status'));
                    }
                    // обновление записи со статусом ВЫПОЛНЕНА при назначении категории ОИТ
                    if ($old_cat->fields['itilcategories_id'] != 133 && $old_cat->fields['id'] != 133 && $ticket->fields['status'] == 3) {
                        $ticket->fields['status'] = 2;
                        $ticket->updateInDB(array('status'));
                    }
                }
            }
        }

        // установка завершающего статуса ПРОСРОЧЕНО или ВЫПОЛНЕНО
        if (!($ticket->isNewItem())) {
            if ($old_cat->fields['itilcategories_id'] == 133 || $old_cat->fields['id'] == 133) {
                if ($item->fields['status'] < $item->input['status'] && $item->input['status'] >= 5 && $item->fields['status'] <= 4) {
                    if (date('Y-m-d H:i:s') > date($ticket->fields['expire_date'])) {
                        $ticket->fields['status'] = 1;
                        $ticket->updateInDB(array('status'));
                    } else {
                        $ticket->fields['status'] = 3;
                        $ticket->updateInDB(array('status'));
                    }
                } else {
                    if (isset($new_cat->fields['id']) && $new_cat->fields['itilcategories_id'] != 133 && $new_cat->fields['id'] != 133 && ($old_cat->fields['itilcategories_id'] == 133 || $old_cat->fields['id'] == 133) && $item->fields['status'] <= 4 && $item->input['status'] <= 4) {
                        if (date('Y-m-d H:i:s') > date($ticket->fields['expire_date'])) {
                            $ticket->fields['status'] = 1;
                            $ticket->updateInDB(array('status'));
                        } else {
                            $ticket->fields['status'] = 3;
                            $ticket->updateInDB(array('status'));
                        }
                    }
                }
            }
        }
    }

    /**
     * Calculate deadline for OITPriority item
     *
     * @param string $date
     * @param integer $prior
     */
    function getExpireDate($date, $prior = 2) {
        $d = new Datetime($date);
        $w_day = $d->format('w');   // день недели
        $h = $d->format('H');       // часы
        $m = $d->format('i');       // минуты

        switch ($prior) {
            case 4:
            case 5:
            case 6: 
                // суббота
                if ($w_day == 6) {
                    $d->add(new DateInterval('P2D'));
                    $d->setTime(12, 30);
                    break;
                }
                // пятница с 17:00 до 23:59
                if ($w_day == 5 && $h >= 17 && $h <=23) {
                    $d->add(new DateInterval('P3D'));
                    $d->setTime(12, 30);
                    break;
                }
                // воскресенье или любой день с 17:00 до 23:59
                if ($w_day == 0 || $h >= 17 && $h <=23) {
                    $d->add(new DateInterval('P1D'));
                    $d->setTime(12, 30);
                    break;
                }
                // любой день с 00:00 до 08:29
                if ($h >= 0 && $h <=7 || $h == 8 && $m <= 29) {
                    $d->setTime(12, 30);
                    break;
                }
                // рабочий день с 08:30 до 12:29
                if ($h == 8 && $m >=30 || $h >= 9 && $h <= 11 || $h == 12 && $m <= 29) {
                    $d->add(new DateInterval('PT4H30M'));
                    break;
                }
                // обеденное время
                if ($h == 12 && $m >=30 || $h == 12 && $m <= 59) {
                    $d->setTime(17, 0);
                    break;
                }
                // рабочий день с 13:00 до 16:59
                if ($h >= 13 && $h <= 16) {
                    $d->add(new DateInterval('PT19H30M'));
                    break;
                }
                break;
            case 3: 
                // суббота
                if ($w_day == 6) {
                    $d->add(new DateInterval('P2D'));
                    $d->setTime(17, 0);
                    break;
                }
                // пятница с 17:00 до 23:59
                if ($w_day == 5 && $h >= 17 && $h <=23) {
                    $d->add(new DateInterval('P3D'));
                    $d->setTime(17, 0);
                    break;
                }
                // пятница, обеденное время
                if ($w_day == 5 && ($h == 12 && $m >=30 || $h == 12 && $m <= 59)) {
                    $d->add(new DateInterval('P3D'));
                    $d->setTime(12, 30);
                    break;
                }
                // пятница с 08:30 до 12:29, с 13:00 до 16:59
                if ($w_day == 5 && ($h == 8 && $m >=30 || $h >= 9 && $h <= 11 || $h == 12 && $m <= 29 || $h >= 13 && $h <= 16)) {
                    $d->add(new DateInterval('P3D'));
                    break;
                }
                // воскресенье или любой день с 17:00 до 23:59
                if ($w_day == 0 || $h >= 17 && $h <=23) {
                    $d->add(new DateInterval('P1D'));
                    $d->setTime(17, 0);
                    break;
                }
                // любой день с 00:00 до 08:29
                if ($h >= 0 && $h <=7 || $h == 8 && $m <= 29) {
                    $d->setTime(17, 0);
                    break;
                }
                // рабочий день с 08:30 до 12:29, с 13:00 до 16:59
                if ($h == 8 && $m >=30 || $h >= 9 && $h <= 11 || $h == 12 && $m <= 29 || $h >= 13 && $h <= 16) {
                    $d->add(new DateInterval('P1D'));
                    break;
                }
                // обеденное время
                if ($h == 12 && $m >=30 || $h == 12 && $m <= 59) {
                    $d->add(new DateInterval('P1D'));
                    $d->setTime(12, 30);
                    break;
                }
                break;
            case 1: 
            case 2:
                // суббота
                if ($w_day == 6) {
                    $d->add(new DateInterval('P3D'));
                    $d->setTime(17, 0);
                    break;
                }
                // пятница с 17:00 до 23:59
                if ($w_day == (5 || 4) && $h >= 17 && $h <=23) {
                    $d->add(new DateInterval('P4D'));
                    $d->setTime(17, 0);
                    break;
                }
                // пятница, обеденное время
                if ($w_day == 5 && ($h == 12 && $m >=30 || $h == 12 && $m <= 59)) {
                    $d->add(new DateInterval('P4D'));
                    $d->setTime(12, 30);
                    break;
                }
                // пятница с 08:30 до 12:29, с 13:00 до 16:59
                if ($w_day == 5 && ($h == 8 && $m >=30 || $h >= 9 && $h <= 11 || $h == 12 && $m <= 29 || $h >= 13 && $h <= 16)) {
                    $d->add(new DateInterval('P4D'));
                    break;
                }
                // четверг с 08:30 до 12:29, с 13:00 до 16:59
                if ($w_day == 4 && ($h == 8 && $m >=30 || $h >= 9 && $h <= 11 || $h == 12 && $m <= 29 || $h >= 13 && $h <= 16)) {
                    $d->add(new DateInterval('P4D'));
                    break;
                }
                // воскресенье или любой день с 17:00 до 23:59
                if ($w_day == 0 || $h >= 17 && $h <=23) {
                    $d->add(new DateInterval('P2D'));
                    $d->setTime(17, 0);
                    break;
                }
                // любой день с 00:00 до 08:29
                if ($h >= 0 && $h <=7 || $h == 8 && $m <= 29) {
                    $d->add(new DateInterval('P1D'));
                    $d->setTime(17, 0);
                    break;
                }
                // рабочий день с 08:30 до 12:29, с 13:00 до 16:59
                if ($h == 8 && $m >=30 || $h >= 9 && $h <= 11 || $h == 12 && $m <= 29 || $h >= 13 && $h <= 16) {
                    $d->add(new DateInterval('P2D'));
                    break;
                }
                // обеденное время
                if ($h == 12 && $m >=30 || $h == 12 && $m <= 59) {
                    $d->add(new DateInterval('P2D'));
                    $d->setTime(12, 30);
                    break;
                }
                break;
        }
        return $d->format('Y-m-d H:i:s');
    }

     /**
     * Modification of priority cells
     *
     * @param string $date
     * @param integer $prior
     */
    function modifyPriority($cat_id, $cur_priority = 2, $status = 1, $assign_date = "", $expire_date = "", $prior_status = 2) {

        $category = new ITILCategory;
        $category->getFromDB($cat_id);
        // входит ли категория в раздел ОИТ
        if ($category->fields['itilcategories_id'] == 133 || $cat_id == 133) {
            $profile_ids = [3, 4, 13];
            if ($expire_date) {
                $msg_priority = 'Приоритет заявок для ОИТ может изменить ваш руководитель!';
            } else {
                $msg_priority = 'Приоритет заявок для ОИТ по умолчанию НИЗКИЙ! Его можно изменить после создания заявки!';
            }

            $p = [
                'name'      => 'priority',
                'value'     => $cur_priority,
                'showtype'  => 'normal',
                'display'   => false,
                'withmajor' => false,
            ];   

            $values[4] = CommonITILObject::getPriorityName(4);
            $values[3] = CommonITILObject::getPriorityName(3);
            $values[2] = CommonITILObject::getPriorityName(2);

            echo "<div id='new_priority'>".Dropdown::showFromArray($p['name'], $values, $p)."</div>";
            echo "
                <script>
                // нечувствительность к регистру для :contains
                jQuery.expr[':'].Contains = jQuery.expr.createPseudo(function(arg) {
                    return function( elem ) {
                        return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
                    };
                });

                let th = $('tr').find('th:Contains(\"приоритет\")');
                let tr = $(th).closest('tr');
                $(th).text('Приоритет (срочность)');
                let td = $(th).next('td');
            ";
            if ($expire_date && in_array(5, $_SESSION["glpigroups"])) {
                echo "
                    function convertSqlDate(date) {
                        let a = date.split(' ');
                        let d = a[0].split('-');
                        let t = a[1].split(':');
                        return new Date(d[0],(d[1]-1),d[2],t[0],t[1],t[2]);
                    }
                    $(tr).after(function() {
                        let out ='<tr><th>Дата назначения</th><td>".$assign_date."</td>';
                        out += '<th>Дедлайн</th><td id=\'deadline\'>".$expire_date."<br>';
                        switch ($prior_status) {
                            case 1: out += '<span id=\'countdown\' style=\'color: red; font-size: larger; font-weight: bold;\'>СРОК НАРУШЕН!</span>'; break;
                            case 2: out += 'Таймер: <span id=\'countdown\' style=\'color: red; font-size: larger; font-weight: bold;\'></span></td></tr>'; break;
                            case 3: out += '<span id=\'countdown\' style=\'color: green; font-size: larger; font-weight: bold;\'>ВЫПОЛНЕНО В СРОК!</span>'; break;
                        }
                        return out;
                    });
                    let currentDate = new Date();
                    let firstDate = convertSqlDate('".$assign_date."');
                    let secondDate = convertSqlDate('".$expire_date."');
                    if ($status <= 4 && $prior_status == 2) {
                        var interval = setInterval(function() {
                            currentDate = new Date();
                            let different = secondDate - currentDate;

                            let hours = Math.floor(different / 3600000);
                            let minutes = Math.floor(((different % 86400000) % 3600000) / 60000);
                            let seconds = Math.round((((different % 86400000) % 3600000) % 60000) / 1000);
                            if (hours <= 0 && minutes <= 0 && seconds <= 0) {
                                $('#deadline').html('".$expire_date."<br><span id=\'countdown\' style=\'color: red; font-size: larger; font-weight: bold;\'>СРОК НАРУШЕН!</span>');
                                
                            } else {
                                hours = (hours < 10) ? '0' + hours : hours;
                                minutes = (minutes < 10) ? '0' + minutes : minutes;
                                seconds = (seconds < 10) ? '0' + seconds : seconds;
                                $('#countdown').html(hours + ':' + minutes + ':' + seconds);
                            }
                        }, 1000);
                    } else {

                    }
                ";
            }
            // изменение доступа к Приоритету в зависимости от статуса заявки
            $out = " 
                $('#new_priority').remove();
                $(td).html('".CommonITILObject::getPriorityName($cur_priority)."<input type=\'hidden\' name=\'priority\' value=\'".$cur_priority."\'>');
                $(tr).before(function() {
                    return '<tr id=\'msg_priority\'><th colspan=\"4\" style=\'color: red; font-size: larger; font-weight: bold;\'>$msg_priority</th><th></th></tr>';
                });
            ";
            if (!(in_array($status, array(5, 6)))) {
                if ($expire_date) {

                    if (in_array($_SESSION["glpiactiveprofile"]['id'], $profile_ids)) {
                        echo "
                            $(td).empty();
                            $(td).append( $('#new_priority') );
                        ";                 
                    } else {
                        echo $out;
                    }
                } else {
                    echo $out;
                }
            } else {
                echo "
                    $('#new_priority').remove();
                    $(td).html('".CommonITILObject::getPriorityName($cur_priority)."');
                ";
            }
            echo "
                </script>
            ";
        }
        return;
    }

    static function getSpecificValueToDisplay($field, $values, array $options=array()) {

        if (!is_array($values)) {
           $values = array($field => $values);
        }
        switch ($field) {
           case 'status':
                $out = self::getStatusName($values[$field]);
                return $out;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

        if (!is_array($values)) {
           $values = array($field => $values);
        }
        $options['display'] = false;
     
        switch ($field) {
            case 'status' :
                $options['value'] = $values[$field];
                return self::dropdownStatus($name, $options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Dropdown of item OITPriotity status
     *
     * @param $name            select name
     * @param $options   array of options:
     *    - value     : integer / preselected value (default 0)
     *    - toadd     : array / array of specific values to add at the begining
     *    - on_change : string / value to transmit to "onChange"
     *    - display   : boolean / display or get string (default true)
     *
     * @return string id of the select
     **/
    static function dropdownStatus($name, $options = []) {

        $params['value']       = 0;
        $params['toadd']       = [];
        $params['on_change']   = '';
        $params['display']     = true;

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $params[$key] = $val;
            }
        }

        $items = [];
        if (count($params['toadd']) > 0) {
            $items = $params['toadd'];
        }

        $items += [
            1 => self::getStatusName(1),
            2 => self::getStatusName(2),
            3 => self::getStatusName(3)
        ];

        return Dropdown::showFromArray($name, $items, $params);
    }

    /**
     * get status name by ID
     *
     * @param integer $key id of the status   
     *
     * @return string name of the status
     **/
    static function getStatusName($key) {
        switch ($key) {
            case 1: return 'Срок нарушен';
            case 2: return 'В работе';
            case 3: return 'Выполнено в срок';
        }
        return false;
    }

}
?>