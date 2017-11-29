$('input[type=checkbox]').on('click', function () {
    var obj = $(this);
    obj.closest('.crl-active-form-group').find('input[type=hidden]').val(obj.prop('checked') ? 1 : 0);
});

$('input[type=radio]').on('click', function () {
    var obj = $(this);
    obj.closest('.crl-active-form-group').find('input[type=hidden]').val(obj.prop('checked') ? 1 : 0);
});
$('.crl-active-form-group.has-error input, .crl-active-form-group.has-error select, .crl-active-form-group.has-error textarea')
    .on('change', function () {
        $(this).closest('.crl-active-form-group').removeClass('has-error');
    });

$('.crl-active-form.with-validation input, .crl-active-form.with-validation select, .crl-active-form.with-validation textarea')
    .not('[type=checkbox], [type=radio]')
    .on('blur', function () {
        var obj = $(this);
        sendValidateAjax(obj, obj.attr('name'), obj.val());
    });
$('.crl-active-form.with-validation input[type=checkbox], .crl-active-form.with-validation input[type=radio]').on('blur', function () {
    var obj = $(this);
    var input = obj.closest('.crl-active-form-group').find('input[type=hidden]');
    sendValidateAjax(obj, input.attr('name'), input.val());
});
$('.crl-active-form').submit(function (event) {
    if ($(this).find('.has-error').length > 0) {
        event.preventDefault();
    }
});

$.fn.validate = function (validator, params, cb) {
    var obj = $(this);
    if (validator) {
        var data = {validator: validator};
        if (obj.is('[type=checkbox]') || obj.is(['[type=radio]'])) {
            data['value'] = obj.closest('.crl-active-from-group').find('input[type=hidden]').val();
        } else {
            data['value'] = obj.val();
        }
        if (params) {
            data['params'] = params;
        }
        $.ajax({
            method: 'post',
            url: 'validator',
            dataType: 'json',
            data: data,
            success: function (data) {
                if (data.success) {
                    obj.closest('.crl-active-from-group').removeClass('has-error').addClass('has-success');
                } else {
                    obj.closest('.crl-active-from-group').removeClass('has-success').addClass('has-error');
                }
                if (cb && typeof cb === 'function') {
                    cb(data, obj);
                }
            }
        });
    } else {

    }
};

function sendValidateAjax(obj, name, value) {
    var data = {validation: true};
    var attributeName = obj.attr('data-attribute');
    if (!attributeName || attributeName.trim().length < 1) {
        return;
    }
    data['attributeName'] = attributeName.trim();
    data[name] = value;
    $.ajax({
        method: 'post',
        url: obj.closest('form').attr('action') || '',
        data: data,
        success: function (data) {
            try {
                data = JSON.parse(data);
                if (data.message) {
                    obj.closest('.crl-active-form-group').find('.' + attributeName + '_help').text(data.message);
                    obj.closest('.crl-active-form-group').removeClass('has-success').addClass('has-error');
                } else {
                    obj.closest('.crl-active-form-group').removeClass('has-error').addClass('has-success');
                }
            } catch (e) {
                obj.closest('.crl-active-form-group').removeClass('has-error').addClass('has-success');
            }
        }
    });

}