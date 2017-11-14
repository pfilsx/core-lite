$('input[type=checkbox]').on('click', function () {
    var obj = $(this);
    obj.closest('.crl-checkbox-group').find('input[type=hidden]').val(obj.prop('checked') ? 1 : 0);
});

$('input[type=radio]').on('click', function () {
    var obj = $(this);
    obj.closest('.crl-radio-group').find('input[type=hidden]').val(obj.prop('checked') ? 1 : 0);
});

$('.crl-active-form.with-validation input').on('blur', function () {
    var data = {validation: true};
    var obj = $(this);
    var fieldName = obj.attr('data-field');
    if (fieldName.trim().length < 1){
        return;
    }
    data['fieldName'] = fieldName;
    data[obj.attr('name')] = obj.val();
    $.ajax({
        method: 'post',
        url: obj.closest('form').attr('action') || '',
        data: data,
        success: function (data) {
            try {
                data = JSON.parse(data);
                if (data.message){
                    obj.closest('.crl-active-form-group').find('.'+ fieldName +'_help').text(data.message);
                    obj.closest('.crl-active-form-group').removeClass('has-success').addClass('has-error');
                } else {
                    obj.closest('.crl-active-form-group').removeClass('has-error').addClass('has-success');
                }
            } catch (e) {
            }
            $('.loader').hide();
        }
    });
});