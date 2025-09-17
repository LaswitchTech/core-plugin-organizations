<article id="layout"></article>
<script>
    (function () {
        $(document).ready(function(){
            builder.Layout('organization',"#layout",{endpoint: '/organizations/fetch?id=<?= $this->Request->getParams('GET', 'id') ?>',disable: ['documents']});
        });
    })();
</script>
