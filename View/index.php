<article id="layout"></article>
<script>
    (function () {
        $(document).ready(function(){
            builder.Layout('index',"#layout",{
                url: '/api/organizations/fetchAll',
                conditions: [],
                selectTools: false,
                dblclick: function(event, table, dt, node, data){
                    window.location.href = "/security/organizations/details?id=" + data.id + "&name=" + encodeURIComponent(data.vcard.name);
                },
                actions: {
                    details:{
                        label:'Details',
                        icon:'eye',
                        action:function(event, table, dt, node, row, data){
                            window.location.href = "/security/organizations/details?id=" + data.id + "&name=" + encodeURIComponent(data.vcard.name);
                        }
                    },
                },
                buttons: [],
                columns: [
                    {
                        targets: 0,
                        visible: false,
                        title: builder.Locale.get('ID'),
                        name: 'id',
                        data: 'id',
                        defaultContent: '',
                    },
                    {
                        targets: 1,
                        visible: true,
                        className: 'all',
                        responsivePriority: 1,
                        title: builder.Locale.get('Name'),
                        name: 'name',
                        data: 'vcard.name',
                        defaultContent: '',
                    },
                    {
                        targets: 2,
                        visible: false,
                        className: 'min-md',
                        responsivePriority: 100,
                        title: builder.Locale.get('DBA'),
                        name: 'dba',
                        data: 'vcard.dba',
                        defaultContent: '',
                    },
                    {
                        targets: 3,
                        visible: true,
                        className: 'min-md',
                        responsivePriority: 10,
                        title: builder.Locale.get('Business Number'),
                        name: 'businessNumber',
                        data: 'vcard.businessNumber',
                        defaultContent: '',
                    },
                    {
                        targets: 4,
                        visible: false,
                        className: 'min-md',
                        responsivePriority: 200,
                        title: builder.Locale.get('Address'),
                        name: 'address',
                        data: 'vcard.address',
                        defaultContent: '',
                    },
                    {
                        targets: 5,
                        visible: false,
                        className: 'min-md',
                        responsivePriority: 300,
                        title: builder.Locale.get('City'),
                        name: 'city',
                        data: 'vcard.city',
                        defaultContent: '',
                    },
                ],
            });
        });
    })();
</script>
