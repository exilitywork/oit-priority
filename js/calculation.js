if($(location).attr('pathname').includes('ticket.php')) {
    let status_col_index = $(`th:contains('По сроку выполнения (для ОИТ)')`).index();
    if(status_col_index > 0) {
        let current_csrf_token = $( "input[name~='_glpi_csrf_token']" ).val();
        let tbody = $(`table:contains('По сроку выполнения (для ОИТ)')`).children('tbody');
        let tr = $(tbody[0]).children('tr');
        for(let i=0, count=tr.size(); i<count-1; i++) {
            let td = $(tr[i]).children('td');
            if($(td[status_col_index]).text() != '') {
                switch($(td[status_col_index]).text()){
                    case 'Срок нарушен':
                        $(tr[i]).css("background-color", "#cf9b9b");
                        break;
                    case 'В работе':
                        $(tr[i]).css("background-color", "#FFC65D");
                        break;
                    case 'Выполнено в срок':
                        $(tr[i]).css("background-color", "#b2e0b6");
                        break;
                }
            }
        }
    }
}