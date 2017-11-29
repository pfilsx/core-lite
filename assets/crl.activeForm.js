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
    .on('blur', function () {
        var obj = $(this);
        obj.validate();
    });
$('.crl-active-form').submit(function (event) {
    if ($(this).find('.has-error').length > 0) {
        event.preventDefault();
    }
});

$.fn.validate = function (validator, params, cb) {
    var obj = $(this);
    var data = {validation: true};
    var url = '';
    if (obj.is('[type=checkbox]') || obj.is(['[type=radio]'])) {
        var input = obj.closest('.crl-active-from-group').find('input[type=hidden]');
        data['value'] = data[input.attr('name')] = input.val();
    } else {
        data['value'] = data[obj.attr('name')] = obj.val();
    }
    if (validator) {
        data['validator'] = validator;
        if (params) {
            data['params'] = params;
        }
        url = window.crl.baseUrl + '/validator';

        $.ajax({
            method: 'post',
            url: window.crl.baseUrl + '/validator',
            dataType: 'json',
            data: data,
            success: function (data) {
                if (data.success) {
                    obj.closest('.crl-active-form-group').removeClass('has-error').addClass('has-success');
                } else {
                    obj.closest('.crl-active-form-group').removeClass('has-success').addClass('has-error');
                }
                if (cb && typeof cb === 'function') {
                    cb(data, obj);
                }
            }
        });

    } else {
        var attributeName = obj.attr('data-attribute');
        if (!attributeName || attributeName.trim().length < 1) {
            return;
        }
        data['attributeName'] = attributeName.trim();
        url = obj.closest('form').attr('action') || '';
    }
    $.ajax({
        method: 'post',
        url: url,
        dataType: 'json',
        data: data,
        success: function (data) {
            if (data.success) {
                obj.closest('.crl-active-form-group').removeClass('has-error').addClass('has-success');
            } else {
                obj.closest('.crl-active-form-group').removeClass('has-success').addClass('has-error');
                obj.closest('.crl-active-form-group').find('.help-block').text(data.message);
            }
            if (cb && typeof cb === 'function') {
                cb(data, obj);
            }
        },
        error: function(){
            obj.closest('.crl-active-form-group').removeClass('has-error').removeClass('has-success');
        }
    });
};