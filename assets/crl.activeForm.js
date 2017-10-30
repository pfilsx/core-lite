$('input[type=checkbox]').on('click', function(){
    var obj = $(this);
    obj.closest('.crl-checkbox-group').find('input[type=hidden]').val(obj.prop('checked') ? 1 : 0);
});

$('input[type=radio]').on('click', function() {
    var obj = $(this);
    obj.closest('.crl-radio-group').find('input[type=hidden]').val(obj.prop('checked') ? 1 : 0);
});