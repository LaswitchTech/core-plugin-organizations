<div class="col-12" id="layout"></div>
<script>
    $(document).ready(function(){
        $.ajax({
            url: '/api/organizations/fetchAll',
            headers: {'X-CSRF-Authorization': CSRF_KEY},
            type: 'POST',dataType: 'json',
            data: {
                conditions: []
            },
            error: function(xhr, status, error) {
                let color = 'info', icon = 'question-circle', title = builder.Locale.get(xhr.statusText), content = builder.Locale.get(xhr.responseText);
                switch(xhr.status){
                    case 403: color = 'danger'; icon = 'exclamation-triangle'; break;
                    case 404: color = 'warning'; icon = 'question-diamond'; break;
                    case 500: color = 'danger'; icon = 'bug'; break;
                }
                builder.Component("alert","#layout",{icon:icon,color:color,title:title},function(alert,component){component.content.html('<pre class="m-0 p-2">'+content+'</pre>');});
            },
            success: function(response) {
                console.log(response);

                // Set Actions
                var actions = {
                    details:{
                        label:'Details',
                        icon:'eye',
                        action:function(event, table, dt, node, row, data){
                            window.location.href = "/plugin/organizations/details?id=" + data.id + "&name=" + encodeURIComponent(data.vcard.name);
                        }
                    },
                };

                // Set Buttons
                var buttons = [];

                // Layout
                builder.Layout(
                    "list",
                    "#layout",
                    {
                        title: builder.Locale.get('Organizations'),
                        icon: 'building',
                        advancedSearch:true,
                        exportTools:true,
                        columnsVisibility:true,
                        selectTools:false,
                        showButtonsLabel: false,
                        dblclick:function(event, table, dt, node, data){
                            actions.details.action(event, table, dt, node, null, data);
                        },
                        actions:actions,
                        buttons:buttons,
                        columnDefs:[
                            { target: 0, visible: false, title: builder.Locale.get('ID'), name: 'id', data: 'id', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(data)
                                return object.prop('outerHTML');
                            }},
                            { target: 1, visible: true, title: builder.Locale.get('Name'), name: 'name', data: 'name', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(row.vcard.name)
                                return object.prop('outerHTML');
                            }},
                            { target: 2, visible: true, title: builder.Locale.get('Doing Business As'), name: 'dba', data: 'dba', render: function(data, type, row) {
                                var object = $(document.createElement('span'))
                                    .addClass('my-2')
                                    .text(row.vcard.dba)
                                return object.prop('outerHTML');
                            }},
                        ],
                    },
                    function(layout, component){

                        // Set container
                        var container = component.card._component.body;

                        // Lower the z-index of the table
                        component.table._component.table.addClass('z-2');

                        // Add Records to Layout
                        for(const [key, record] of Object.entries(response.records)){
                            layout.add(record);
                        }
                    },
                );
            },
        });
    });
</script>
