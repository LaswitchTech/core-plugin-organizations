<div class="col-12" id="layout"></div>
<script>
    (async function () {
        await builder.Storage._ensureReady?.();
        $(document).ready(function(){
            $.ajax({
                url: '/api/organizations/fetch?id=<?= $this->Request->getParams('GET', 'id') ?>',
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
                success: async function(response) {

                    // Configure Storage
                    builder.Storage.setKey(`organization:${response.record.id}`);
                    await builder.Storage.set(response);
                    console.log(await builder.Storage.get());

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

                    // Create the Details Card
                    const Details = builder.Component(
                        "card",
                        element.col1,
                        {
                            icon: "building",
                            title: builder.Locale.get('Details'),
                        },
                        async function(card,component){

                            // Retrieve the record
                            let record = await builder.Storage.get('record');

                            // Styling
                            component.body.addClass('d-flex flex-column justify-content-center align-items-center');

                            // Insert the organization's profile picture
                            component.body.avatar = $(document.createElement('div')).addClass('rounded-circle border border-3 border-light d-flex justify-content-center align-items-center position-relative').css({"height": "256px", "width": "256px"}).appendTo(component.body);
                            component.body.avatar.img = $(document.createElement('img')).attr({
                                "class": "rounded-circle",
                                "src": '/plugin/organizations/logo?id=' + record.id,
                                "data-type": "avatar",
                                "data-vcard": record.vcard.id,
                                "style": "max-height: 250px; max-width: 250px; height: 250px; width: 250px; object-fit: contain; object-position: center;",
                            }).appendTo(component.body.avatar);
                            component.body.avatar.btn = $(document.createElement('button')).attr({
                                "type": "button",
                                "class": "btn btn-sm btn-info fs-5 rounded-circle position-absolute",
                                "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; bottom: 8px; right: 8px;",
                            }).html('<i class="bi bi-upload"></i>').appendTo(component.body.avatar);
                            component.body.avatar.btn.click(function(){
                                vCardModalAvatar(record.vcard);
                            });

                            // Insert the organization's name
                            component.body.name = $(document.createElement('div')).addClass('position-relative mt-2 text-center').appendTo(component.body);
                            component.body.name.string = $(document.createElement('h2')).addClass('fw-lighter m-0').text(record.vcard.name).appendTo(component.body.name);
                            component.body.name.btn = $(document.createElement('button')).attr({
                                "type": "button",
                                "class": "btn btn-sm btn-warning fs-5 rounded-circle position-absolute",
                                "style": "transition: all 0.5s ease-in-out; height: 48px!important; width: 48px!important; top: calc(50% - 24px); right: -56px;",
                            }).html('<i class="bi bi-pencil"></i>').appendTo(component.body.name);
                            component.body.name.btn.click(function(){
                                vCardModalEdit(record.vcard);
                            });
                        },
                    );

                    // Create a Tabs component
                    const Tabs = builder.Component(
                        "tabs",
                        element.col2,
                        {
                            class: {
                                navbar: 'nav-pills',
                            },
                        },
                        async function(tabs,card){

                            // Retrieve the record
                            let record = await builder.Storage.get('record');

                            // Set the table
                            let table = 'organizations'

                            // Styling
                            card._component.body.removeClass('card-body');

                            // Users
                            tabs.add(
                                'users',
                                {
                                    icon: "person",
                                    label: builder.Locale.get("Members"),
                                },
                                async function(tab,nav){

                                    // Retrieve the users
                                    let users = await builder.Storage.get('dependencies:users');

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

                                                // AJAX Request
                                                $.ajax({
                                                    url: '/api/organizations/update?id='+record.id,
                                                    headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                    type: 'POST',dataType: 'json',
                                                    data: ajaxData,
                                                    success: function(response) {

                                                        // Update the table
                                                        table.delete(row);
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
                                                    url: '/api/auth/users',
                                                    type: 'GET',dataType: 'json',
                                                    success: function(response) {

                                                        // Retrieve existing members
                                                        var members = []
                                                        for(const [key, row] of Object.entries(dt.data().toArray())){
                                                            members.push(row.id);
                                                        }

                                                        // Build options
                                                        var options = [];
                                                        for(const [key, user] of Object.entries(response.records)){
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
                                                                component.addClass('modal-success');
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
                                                                                dt.row.add(response.records[form.val()]).draw();

                                                                                // Add the user to the list of members
                                                                                members.push(form.val());

                                                                                // AJAX Request
                                                                                $.ajax({
                                                                                    url: '/api/organizations/update?id='+record.id,
                                                                                    headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                                                    type: 'POST',dataType: 'json',
                                                                                    data: {users: members},
                                                                                    success: function(response) {

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
                                            for(const [key, user] of Object.entries(users ?? {})){
                                                table.add(user);
                                            }
                                        },
                                    );
                                },
                            );

                            // Notes
                            <?php if($this->Helper->Core->isInstalled('notes')): ?>

                                // Retrieve the notes
                                let notes = await builder.Storage.get('dependencies:notes');

                                // Add the Notes tab
                                tabs.add(
                                    'notes',
                                    {
                                        icon: "stickies",
                                        label: builder.Locale.get("Notes"),
                                    },
                                    function(tab,nav){
                                        card.notes = tab;
                                        NotesFeed(notes ?? [], tab, table, record.id);
                                    },
                                );
                            <?php endif; ?>

                            // Contacts
                            <?php if($this->Helper->Core->isInstalled('contacts')): ?>

                                // Retrieve the contacts
                                let contacts = await builder.Storage.get('dependencies:contacts');

                                // Add the Contacts tab
                                tabs.add(
                                    'contacts',
                                    {
                                        icon: "person-vcard",
                                        label: builder.Locale.get("Contacts"),
                                    },
                                    function(tab,nav){
                                        card.contacts = tab;
                                        ContactsFeed(contacts ?? [], tab, {
                                            "category": "Contact",
                                            "address": record.vcard.address,
                                            "city": record.vcard.city,
                                            "country": record.vcard.country.code,
                                            "state": record.vcard.state.code,
                                            "zipcode": record.vcard.zipcode,
                                            "locale": record.vcard.locale,
                                            "phone": record.vcard.phone,
                                            "targetTable": table,
                                            "targetId": record.id,
                                        });
                                    },
                                );
                            <?php endif; ?>

                            // Files
                            <?php if($this->Helper->Core->isInstalled('files')): ?>

                                // Retrieve the files
                                let files = await builder.Storage.get('dependencies:files');

                                // Add the Files tab
                                tabs.add(
                                    'files',
                                    {
                                        icon: "file-earmark",
                                        label: builder.Locale.get("Files"),
                                    },
                                    function(tab,nav){
                                        card.files = tab;
                                        FilesFeed(files ?? [], tab, {
                                            targetTable: table,
                                            targetId: record.id,
                                            isPublic: 1,
                                        });
                                    },
                                );
                            <?php endif; ?>

                            // Event
                            <?php if($this->Helper->Core->isInstalled('event')): ?>

                                // Retrieve the event
                                let event = await builder.Storage.get('dependencies:event');

                                // Add the Event tab
                                tabs.add(
                                    'activities',
                                    {
                                        icon: "activity",
                                        label: builder.Locale.get("Activity"),
                                    },
                                    function(tab,nav){
                                        tab.addClass('px-4 py-3');
                                        card.activities = tab;
                                        EventFeed(event ?? [], tab);
                                    },
                                );
                            <?php endif; ?>

                            // Relationship
                            <?php if($this->Helper->Core->isInstalled('relationship')): ?>

                                // Retrieve the relationship
                                let relationship = await builder.Storage.get('dependencies:relationship');

                                // Add the Relationship tab
                                tabs.add(
                                    'related',
                                    {
                                        icon: "diagram-2",
                                        label: builder.Locale.get("Related"),
                                    },
                                    function(tab,nav){
                                        tab.addClass('px-4 py-3');
                                        card.related = tab;
                                        RelationshipFeed(relationship, tab, table, record.id, function(feed){
                                            card.related.feed = feed;
                                        });
                                    },
                                );
                            <?php endif; ?>
                        },
                    );
                },
            });
        });
    })();
</script>
