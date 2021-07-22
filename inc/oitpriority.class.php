<?php
/**
 * -------------------------------------------------------------------------
 * Rentcontracts plugin for GLPI
 * Copyright (C) 2021 by the Belwest, Kapeshko Oleg.
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Rentcontracts.
 *
 * Rentcontracts is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Rentcontracts is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Rentcontracts. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

class PluginOitpriorityCalculation extends CommonDBTM {

    /**
     * Modify default values of fields on knowbaseitem form
     *
     * @param array $params [item, options]
     */
    static function pre_item_form($params) {
        $item       = $params['item'];
        $options    = $params['options'];

        //if ($item->getType() == 'Contract' && !($item->getID())) {
        if (($item->getID())) {
            //print_r($params);
            //$item->fields['is_faq'] = 1;
            //$item->fields['begin_date'] = date("Y-m-d H:i");
        }
    }
    
    /**
     * Modify default values of fields on knowbaseitem form
     *
     * @param array $params [item, options]
     */
    static function post_item_form($params) {
        $item       = $params['item'];
        $options    = $params['options'];
        //$rent = getItemForItemtype($item->getType());
//print_r($item['id']);
        if ($item->getType() == 'Contract' && isset($options['withtemplate'])) {
            /*$dr = ContractType::dropdown([
                'value' => $this->fields["contracttypes_id"]
                'on_change' => 'this.form.submit()'
                ]);*/
            //print_r($options);
            echo '
                <script>
                    let tdc = $("#mainformtable").children("tbody").children("tr");
                    //tdc.empty();
                    tdc.each(function(index) {
                        if (this.id == "rentraw") {
                            return false;
                        }
                        if (index == 0) {
                            $(this).html("<th colspan=\"4\">Основная информация по договору</th>");
                        }
                        if (index == 1) {
                            let td = $(this).children("td");
                            $(td[0]).append(" (№ отделения)");
                        }
                        if (index > 1) {
                            $(this).remove();
                        }
                    });
                    //tdc.html("<tr><td>table</td></tr>");
                </script>
            ';
                    /*tdc.text("");
                    //tdc.append($("#dropdown_contracttypes_id'.ContractType::dropdown([
                        //'value' => $this->fields["contracttypes_id"]
                        //'on_change' => 'this.form.submit()'
                        ]).'").closest("span"));
                    //$("select[name=\"contracttypes_id\"]").on("change", function(e) { 
                        //this.form.submit();
                    //});
                </script>
            ';*/
            echo '<tr id="rentraw"><td>Арендодатель</td><td>';
            Html::autocompletionTextField($item, "rent_landlord");
            echo '</td><td>Номер и дата договора</td><td>';
            Html::autocompletionTextField($item, "num");
            echo '</td></tr>';
            echo '<tr><td>Тип торгового объекта</td><td>';
            Dropdown::showFromArray('rent_obj_type', 
                                    [0 => Dropdown::EMPTY_VALUE,
                                     1 => 'стрит', 
                                     2 => 'ТЦ'
                                    ],
                                    [//'value' => $graph['line'], 
                                     'display_emptychoice' => true, 
                                     'display' => true
                                    ]);
            echo '</td></tr>';
            echo '<tr><td>Адрес</td><td>';
            Html::autocompletionTextField($item, "rent_address");
            echo '</td><td></td><td></td></tr>';
            echo '<tr><th colspan="4">Информация по срокам</th></tr>';
            echo '<tr><td>Дата начала</td><td>';
            Html::showDateField("begin_date", ['value' => $item->fields["begin_date"]]);
            echo '</td><td>Дата окончания</td><td id="rent_end_date">';
            if (!empty($item->fields["begin_date"]) && $item->fields["duration"] > 0) {
                echo " -> ".self::getWarrantyExpir($item->fields["begin_date"],
                                               $item->fields["duration"], 0, true, false);
            }
            echo '</td></tr>';
            echo '<tr><td>Срок договора</td><td>';
            Dropdown::showNumber("duration", ['value' => $item->fields["duration"],
                                             'min'   => 1,
                                             'max'   => 120,
                                             'step'  => 1,
                                             'toadd' => [0 => 'Точная дата', -1 => 'Указать в днях'],
                                             'unit'  => 'month',
                                             'on_change' => 'calcEndDate($(this).val());']);
            echo '</td><td></td><td></td></tr>';
            echo '<tr><td>Пролонгация</td><td>';
            Contract::dropdownContractRenewal("renewal", $item->fields["renewal"]);
            echo '</td><td>Количество пролонгаций</td><td>';
            Dropdown::showNumber("rent_countrenew", [
                                            //'value' => $item->fields["rent_countrenew"],
                                             'min'   => 1,
                                             'max'   => 10,
                                             'step'  => 1,
                                             'toadd' => [0 => Dropdown::EMPTY_VALUE],
                                             //'unit'  => 'month'
                                             ]);
            echo '</td></tr>';
            echo '<tr><td>Примечание</td><td>';
            Html::autocompletionTextField($item, "rent_commentrenew");
            echo '</td><td></td><td></td></tr>';
            echo '<tr><th colspan="4">Отказ от исполнения договора</th></tr>';
            echo '<tr><td>Срок</td><td>';
            Dropdown::showFromArray('rent_refuse',
                                    [0 => 'Без отказа',
                                    -1 => 'Указать срок',
                                     1 => '1 месяц', 
                                     2 => '2 месяца',
                                     3 => '3 месяца',
                                     4 => '30 дней',
                                     5 => '60 дней',
                                     6 => '90 дней'
                                    ],
                                    [//'value' => $graph['line'], 
                                     //'display_emptychoice' => true, 
                                     'display' => true
                                    ]);
            echo '</td><td>Примечание</td><td>';
            Html::autocompletionTextField($item, "rent_comment1refuse");
            echo '</td></tr>';
            echo '<tr><td>Пункт договора</td><td>';
            Html::autocompletionTextField($item, "rent_sectionrefuse");
            echo '</td><td></td><td></td></tr>';
            echo '<tr><td colspan="2">Отсутствие возможности одностороннего отказа</td>';
            echo '<td>Примечание</td><td>';
            Html::autocompletionTextField($item, "rent_comment2refuse");
            echo '</td></tr>';
            echo '<tr><td>C</td><td>';
            Html::showDateField("rent_refuseban_begin", [/*'value' => $item->fields["begin_date"]*/]);
            echo '</td><td>по</td><td>';
            Html::showDateField("rent_refuseban_end", [/*'value' => $item->fields["begin_date"]*/]);
            echo '</td></tr>';
            echo '<tr><th colspan="4">Уведомление о намерении пролонгации</th></tr>';
            echo '<tr><td>Уведомление о пролонгации</td><td>';
            Dropdown::showFromArray('rent_notice', 
                                    [0 => 'Без уведомлений',
                                    -1 => 'Указать срок', 
                                     1 => '1 месяц', 
                                     2 => '2 месяца',
                                     3 => '3 месяца',
                                     4 => '30 дней',
                                     5 => '60 дней',
                                     6 => '90 дней'
                                    ],
                                    [//'value' => $graph['line'], 
                                     //'display_emptychoice' => true, 
                                     'display' => true
                                    ]);
            echo '</td><td>Примечание</td><td>';
            Html::autocompletionTextField($item, "rent_commentnotice");
            echo '</td></tr>';

            //$item->fields['is_faq'] = 1;
            //$item->fields['begin_date'] = date("Y-m-d H:i");
            echo '
                <script>
                    function calcEndDate(duration) {
                        if (duration < 0) {
                            $("select[name=\"duration\"]").closest("td").append("<input name=\"rent_duration_days\" min=\"0\" step=\"1\" style=\"width: 5em;\" value=\"'.'\">");
                        } else {
                            $("input[name=\"rent_duration_days\"]").remove();
                        }

                        $.ajax({
                            type: "POST",
                            url: "../plugins/rentcontracts/ajax/calcenddate.php",
                            data:{
                                duration : duration,
                                begin_date : $("input[name=\"begin_date\"]").val()
                            },
                            dataType: "json",
                            success: function(data) {
                                $(".preloader_bg, .preloader_content").fadeOut(0);
                                if (data.alert) {
                                    alert("Сначала установите дату начала действия договора!")
                                } else {
                                    $("#rent_end_date").html(data.end_date);
                                }
                                console.log(data.total);
                                //$("#rent_end_date").html(data);
                                //alert(data.alert);
                            },
                            error: function() {
                                $(".preloader_bg, .preloader_content").fadeOut(0);
                                alert("Внимание! Ошибка сохранения данных!");
                            }
                        });
                    }
                </script>
            ';
        }
    }

    /**
     * Get date using a begin date and a period in dat & month
     *
     * @param $from            date     begin date
     * @param $addwarranty     integer  period in months
     * @param $deletenotice    integer  period in months of notice (default 0)
     * @param $color           boolean  if show expire date in red color (false by default)
     * @param $ismonth         boolean  type of $addwarranty - month or day (true by default)
     *
     * @return expiration date string
    **/
    static function getWarrantyExpir($from, $addwarranty, $deletenotice = 0, $color = false, $ismonth = true) {
        
        /*if ($ismonth) {
            return Infocom::getWarrantyExpir($from, $addwarranty, $deletenotice, $color);
        }*/

        // Life warranty
        if (($addwarranty == -1)
            && ($deletenotice == 0)) {
        return __('Never');
        }

        if (($from == null) || empty($from)) {
        return "";
        }
        if ($ismonth) {
            $datetime = strtotime("$from+$addwarranty month -$deletenotice month -1 day");
        } else {
            $datetime = strtotime("$from+$addwarranty day -$deletenotice day -1 day");
        }
        if ($color && ($datetime < time())) {
        return "<span class='red'>".Html::convDate(date("Y-m-d", $datetime))."</span>";
        }
        return Html::convDate(date("Y-m-d", $datetime));
    }
}
?>