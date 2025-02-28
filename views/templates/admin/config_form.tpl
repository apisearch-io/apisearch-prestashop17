<script>
    document.getElementById("go-to-admin").setAttribute("target", "_blank");
    tabRealTimePrices();

    $("[name=AS_REAL_TIME_PRICES]").on('change', function() {
        tabRealTimePrices();
    })

    // Methods
    function tabRealTimePrices() {
        if ($("#AS_REAL_TIME_PRICES_on")[0].checked) {
            $('.as_groups_show_no_tax').first().closest('.form-group').show();
        } else {
            $('.as_groups_show_no_tax').first().closest('.form-group').hide();
        }
    }
</script>