<!--
  Core Framework - View File

  @license    MIT (https://mit-license.org/)
  @author     Louis Ouellet <louis@laswitchtech.com>
-->
<div class="col-12" id="layout"></div>
<script>
    $(document).ready(function(){
        $.ajax({
            url: '/endpoint.php/organizations/fetch?id=<?= $this->Request->getParams('GET', 'id') ?>',
            type: 'GET',dataType: 'json',
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

                // Set the color, icon and label
                var color = ['secondary','primary','success','warning','danger'];
                var icon = ['ban','eye','plus-lg','pencil','trash'];
                var label = ['None','Read','Create','Update','Delete'];

                // Set the element
                var element = $('#layout');

                // Setup the layout
                element.row = $(document.createElement('div')).addClass('row').appendTo(element);
                element.col1 = $(document.createElement('div')).addClass('col-12 col-md-6 col-lg-4').appendTo(element.row);
                element.col2 = $(document.createElement('div')).addClass('col-12 col-md-6 col-lg-8').appendTo(element.row);

                var contacts = [];
                for(const [key, value] of Object.entries(response.record.contacts ?? {})){
                    var text = value.vcard.name;
                    if(value.vcard.title != null){
                        text += ' - ' + value.vcard.title;
                    }
                    contacts.push({id:value.vcard.id,text:text});
                }

                // Create the Details Card
                builder.Component(
                    "card",
                    element.col1,
                    {
                        icon: "building",
                        title: builder.Locale.get('Details'),
                    },
                    function(card,component){

                        // Styling
                        component.body.addClass('d-flex flex-column justify-content-center align-items-center');

                        // Insert the organization's profile picture
                        component.body.avatar = $(document.createElement('div')).addClass('rounded-circle border border-3 border-light d-flex justify-content-center align-items-center position-relative').css({"height": "256px", "width": "256px"}).appendTo(component.body);
                        component.body.avatar.img = $(document.createElement('img')).attr({
                            "class": "rounded-circle",
                            "src": '/plugin/organizations/logo?id=' + response.record.id,
                            "data-type": "avatar",
                            "data-vcard": response.record.vcard.id,
                            "style": "max-height: 250px; max-width: 250px; height: 250px; width: 250px; object-fit: contain; object-position: center;",
                        }).appendTo(component.body.avatar);
                        component.body.avatar.btn = $(document.createElement('button')).attr({
                            "type": "button",
                            "class": "btn btn-sm btn-info fs-5 rounded-circle position-absolute",
                            "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; bottom: 8px; right: 8px;",
                        }).html('<i class="bi bi-upload"></i>').appendTo(component.body.avatar);
                        component.body.avatar.btn.click(function(){
                            vCardModalAvatar(response.record.vcard);
                        });

                        // Insert the organization's name
                        component.body.name = $(document.createElement('div')).addClass('position-relative mt-2 text-center').appendTo(component.body);
                        component.body.name.string = $(document.createElement('h2')).addClass('fw-lighter m-0').text(response.record.vcard.name).appendTo(component.body.name);
                        component.body.name.btn = $(document.createElement('button')).attr({
                            "type": "button",
                            "class": "btn btn-sm btn-warning fs-5 rounded-circle position-absolute",
                            "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; top: calc(50% - 24px); right: -56px;",
                        }).html('<i class="bi bi-pencil"></i>').appendTo(component.body.name);
                        component.body.name.btn.click(function(){
                            vCardModalEdit(response.record.vcard);
                        });
                    },
                );

                // Create a Tabs component
                builder.Component(
                    "tabs",
                    element.col2,
                    {
                        class: {
                            navbar: 'nav-pills',
                        },
                    },
                    function(tabs,card){
                        card._component.body.removeClass('card-body');
                        tabs.add(
                            'users',
                            {
                                icon: "person",
                                label: builder.Locale.get("Members"),
                            },
                            function(tab,nav){
                                var actions = {
                                    details:{
                                        label:'Details',
                                        icon:'eye',
                                        action:function(event, table, dt, node, row, data){
                                            window.location.href = "/plugin/users/details?id=" + data.id + "&name=" + data.username;
                                        }
                                    },
                                    remove:{
                                        label:'Remove',
                                        icon:'trash',
                                        action:function(event, table, dt, node, row, data){

                                            // Update the table
                                            table.delete(row);

                                            // Retrieve the users
                                            var users = [];
                                            for(const [key, record] of Object.entries(table.data())){
                                                if(data.id !== record.id){
                                                    users.push(record.id);
                                                }
                                            }

                                            // Set the AJAX DATA
                                            var ajaxData = {
                                                users: JSON.stringify(users),
                                            };
                                            ajaxData[CSRF_KEY] = CSRF_TOKEN;

                                            // AJAX Request
                                            $.ajax({
                                                url: '/endpoint.php/organizations/update?id='+response.record.id,
                                                type: 'POST',dataType: 'json',
                                                data: ajaxData,
                                                success: function(response) {

                                                    // Update the CSRF Token
                                                    CSRF_KEY = response.CSRF.key;
                                                    CSRF_TOKEN = response.CSRF.token;
                                                }
                                            });
                                        },
                                    },
                                };
                                var buttons = [
                                    {
                                        className : 'btn-success',
                                        init: function (dt, node){
                                            $(node).removeClass('btn-secondary');
                                        },
                                        text: '<i class="bi bi-plus-lg me-2"></i>'+builder.Locale.get('Add User'),
                                        action:function(event, dt, node, config){

                                            // AJAX Request
                                            $.ajax({
                                                url: '/endpoint.php/organizations/users',
                                                type: 'GET',dataType: 'json',
                                                success: function(users) {

                                                    // Retrieve existing members
                                                    var members = []
                                                    for(const [key, record] of Object.entries(dt.data().toArray())){
                                                        members.push(record.id);
                                                    }

                                                    // Build options
                                                    var options = [];
                                                    for(const [key, user] of Object.entries(users)){
                                                        if($.inArray(user.id, members) === -1){
                                                            options.push({id: user.id, text: user.username+' - '+user.vcard.name});
                                                        }
                                                    }

                                                    // Create a modal with a form
                                                    builder.Component(
                                                        "modal",
                                                        {
                                                            onEnter: false,
                                                            destroy: true,
                                                            icon: "plus-lg",
                                                            title: builder.Locale.get('Add User'),
                                                            cancel: false,
                                                            submit: true,
                                                            callback: {
                                                                submit: function(element,modal){
                                                                    element.form.submit();
                                                                },
                                                            },
                                                        },
                                                        function(modal,component){
                                                            const componentModal = component;
                                                            component.header.addClass('text-bg-success');
                                                            component.footer.submit.addClass('btn-success').removeClass('btn-link').attr({
                                                                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
                                                            }).text(builder.Locale.get('Add'));
                                                            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-plus-lg me-1').prependTo(component.footer.submit);
                                                            component.form = builder.Component(
                                                                'form',
                                                                component.body,
                                                                {
                                                                    class:{
                                                                        form: 'row row-cols-3',
                                                                        field: 'col',
                                                                    },
                                                                    callback:{
                                                                        val: function(values){
                                                                            return parseInt(values.user);
                                                                        },
                                                                        submit: function(form){

                                                                            // Add the record to the table
                                                                            dt.row.add(users[form.val()]).draw();

                                                                            // Add the user to the list of members
                                                                            members.push(form.val());

                                                                            // Set the AJAX DATA
                                                                            var ajaxData = {
                                                                                users: members,
                                                                            };
                                                                            ajaxData[CSRF_KEY] = CSRF_TOKEN;

                                                                            // AJAX Request
                                                                            $.ajax({
                                                                                url: '/endpoint.php/organizations/update?id='+response.record.id,
                                                                                type: 'POST',dataType: 'json',
                                                                                data: ajaxData,
                                                                                success: function(response) {

                                                                                    // Update the CSRF Token
                                                                                    CSRF_KEY = response.CSRF.key;
                                                                                    CSRF_TOKEN = response.CSRF.token;

                                                                                    // Hide the modal
                                                                                    modal.hide();
                                                                                }
                                                                            });
                                                                        },
                                                                    },
                                                                },
                                                                function(form,component){

                                                                    // user
                                                                    form.add(
                                                                        {
                                                                            name: 'user',
                                                                            label: builder.Locale.get('User'),
                                                                            icon: 'people',
                                                                            type: 'select2',
                                                                            options: options,
                                                                            modal: componentModal,
                                                                            class: {
                                                                                field: 'col-12',
                                                                            },
                                                                        }
                                                                    );

                                                                    // Show the modal
                                                                    modal.show();
                                                                },
                                                            );
                                                        },
                                                    );
                                                }
                                            });
                                        },
                                    }
                                ];
                                builder.Component(
                                    "table",
                                    tab,
                                    {
                                        class: {
                                            buttons: "px-4 pt-4",
                                            table: "border-top",
                                            footer: "px-4 pt-2 pb-4",
                                        },
                                        showButtonsLabel: false,
                                        selectTools:false,
                                        actions:actions,
                                        dblclick:function(event, table, dt, node, data){
                                            actions.details.action(event, table, dt, node, null, data);
                                        },
                                        datatable:{
                                            columnDefs:[
                                                { target: 0, visible: false, responsivePriority: 1000, title: builder.Locale.get('ID'), name: 'id', data: 'id' },
                                                { target: 1, visible: true, responsivePriority: 1, title: builder.Locale.get('Username'), name: 'username', data: 'username' },
                                            ],
                                            buttons: buttons,
                                        },
                                    },
                                    function(table,component){
                                        for(const [key, record] of Object.entries(response.record.users ?? {})){
                                            table.add(record);
                                        }
                                    },
                                );
                            },
                        );
                        tabs.add(
                            'notes',
                            {
                                icon: "stickies",
                                label: builder.Locale.get("Notes"),
                            },
                            function(tab,nav){
                                NotesFeed(response.record.notes ?? {}, tab, 'organizations', response.record.id);
                            },
                        );
                        tabs.add(
                            'contacts',
                            {
                                icon: "person-vcard",
                                label: builder.Locale.get("Contacts"),
                            },
                            function(tab,nav){
                                ContactsFeed(response.record.contacts ?? {}, tab, {
                                    "address": response.record.vcard.address,
                                    "city": response.record.vcard.city,
                                    "country": response.record.vcard.country,
                                    "state": response.record.vcard.state,
                                    "zipcode": response.record.vcard.zipcode,
                                    "locale": response.record.vcard.locale,
                                    "phone": response.record.vcard.phone,
                                    "targetTable": "organizations",
                                    "targetId": response.record.id,
                                });
                            },
                        );
                        tabs.add(
                            'files',
                            {
                                icon: "file-earmark",
                                label: builder.Locale.get("Files"),
                            },
                            function(tab,nav){
                                FilesFeed(response.record.files ?? {}, tab, {
                                    targetTable: "organizations",
                                    targetId: response.record.id,
                                    isPublic: 1,
                                });
                            },
                        );
                        tabs.add(
                            'activities',
                            {
                                icon: "activity",
                                label: builder.Locale.get("Activity"),
                            },
                            function(tab,nav){
                                tab.addClass('px-4 py-3');
                                EventFeed(response.record.events ?? {}, tab);
                            },
                        );
                        tabs.add(
                            'related',
                            {
                                icon: "diagram-2",
                                label: builder.Locale.get("Related"),
                            },
                            function(tab,nav){
                                tab.addClass('px-4 py-3');
                                RelationshipFeed(response.relationships ?? [], tab);
                            },
                        );
                    },
                );
            },
        });
    });
</script>
