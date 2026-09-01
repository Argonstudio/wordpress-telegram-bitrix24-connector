/**
 * WordPress Telegram & Bitrix24 Connector
 *
 * @author    Original code base by Alexander Maltsev https://github.com/itchief/feedback-form/tree/master
 * @author    Refactored & updated by Ivan Voitkov | Argon Studio (https://argon-studio.ru/)
 * @author    AI Assistant (Code reconstruction & optimization)
 * 
 * @copyright 2018 Alexander Maltsev (Original parts)
 * @copyright 2026 Ivan Voitkov (Refactored version)
 * @license   MIT License
 * @link      https://github.com/Argonstudio/wordpress-telegram-bitrix24-connector/
 */

// ============================================================
// Класс обработки форм (для формы в подвале и любых других)
// ============================================================
class ItcSubmitForm {
    static instances = [];

    static getOrCreateInstance(target, config) {
        const elForm = typeof target === 'string' ? document.querySelector(target) : target;
        const found = this.instances.find(el => el.target === elForm);

        if (found) {
            return found.instance;
        }

        const form = new this(elForm, config);
        this.instances.push({ target: elForm, instance: form });

        return form;
    }

    constructor(target, config = {}) {
        this._isCheckValidationOnClient = config['isCheckValidationOnClient'] !== false;
        this._elForm = target;
        this._init();
    }

    // Получение новой капчи
    _reloadCaptcha() {
        const captchaImg = this._elForm.querySelector('.form-captcha__image');
        if (!captchaImg) return;

        const captchaSrc = captchaImg.getAttribute('data-src');
        const captchaPrefix = captchaSrc.indexOf('?id') !== -1 ? '&rnd=' : '?rnd=';
        const captchaNewSrc = captchaSrc + captchaPrefix + new Date().getTime();

        captchaImg.setAttribute('src', captchaNewSrc);
    }

    // Установка статуса валидации
    _setStateValidation(input, state, message) {
        if (!input.closest('.form-group')) return;

        const formGroup = input.closest('.form-group');
        const errorBlock = formGroup.querySelector('.error-feedback');
        if (!errorBlock) return;

        const className = state === 'success' ? 'is-valid' : 'is-error';
        const text = state === 'success' ? '' : message;

        input.classList.remove('is-valid', 'is-error');
        errorBlock.textContent = '';
        errorBlock.style.display = '';

        if (state === 'error' || state === 'success') {
            input.classList.add(className);
            if (text !== '') {
                errorBlock.textContent = text;
                errorBlock.style.display = 'block';
            }
        }
    }

    // Валидация формы
    _checkValidity() {
        let valid = true;

        this._elForm.querySelectorAll('input, textarea').forEach(el => {
            if (el.checkValidity()) {
                this._setStateValidation(el, 'success');
            } else {
                this._setStateValidation(el, 'error', el.validationMessage);
                valid = false;
            }

            // Дополнительная валидация телефона
            if (el.id === 'phone') {
                let phoneValidation = "+7 " + el.value;
                if (!/^(?:\+7|8) \(\d{3}\) \d{3}-\d{2}-\d{2}$/.test(phoneValidation)) {
                    this._setStateValidation(el, 'error', "Телефон введен неверно");
                    valid = false;
                }
            }

            // Проверка длины
            if (el.nodeName === 'INPUT' && el.value.length > 255) {
                this._setStateValidation(el, 'error', "Значение не должно превышать 255 символов");
                valid = false;
            } else if (el.nodeName === 'TEXTAREA' && el.value.length > 4096) {
                this._setStateValidation(el, 'error', "Значение не должно превышать 4096 символов");
                valid = false;
            }
        });

        return valid;
    }

    // Собираем данные для отправки на сервер
    _getFormData() {
        return new FormData(this._elForm);
    }

    // При получении успешного ответа от сервера
    _successXHR(data) {
        this._elForm.querySelectorAll('input, textarea').forEach(el => {
            this._setStateValidation(el);
        });

        if (data && data['result'] === 'success') {
            this._elForm.dispatchEvent(new Event('itc.successSendForm', { bubbles: true }));
            return;
        }

        const formError = this._elForm.querySelector('.form-error');
        if (formError) {
            formError.classList.remove('form-error_hide');

            if (!data || !data['errors'] || !Object.keys(data['errors']).length) {
                formError.textContent = 'При отправке сообщения произошла ошибка. Пожалуйста, попробуйте ещё раз позже.';
            } else {
                formError.textContent = 'В форме содержатся ошибки!';
            }
        }

        // Выводим ошибки
        if (data && data['errors']) {
            for (let key in data['errors']) {
                if (key === 'captcha') {
                    this._reloadCaptcha();
                }
                const el = this._elForm.querySelector('[name="' + key + '"]');
                if (el) {
                    this._setStateValidation(el, 'error', data['errors'][key]);
                }
            }
        }

        // К полям, отвечающим требованиям, добавляем класс is-valid
        this._elForm.querySelectorAll('input:not(.is-error), textarea:not(.is-error)').forEach(el => {
            this._setStateValidation(el, 'success', '');
        });

        // Устанавливаем фокус на невалидный элемент
        const elError = this._elForm.querySelector('.is-error');
        if (elError) {
            elError.focus();
        }
    }

    _errorXHR() {
        const formError = this._elForm.querySelector('.form-error');
        if (formError) {
            formError.classList.remove('d-none');
            formError.textContent = 'Ошибка сервера. Попробуйте ещё раз.';
            formError.classList.remove('form-error_hide');
        }
    }

    // Отправка формы
    _onSubmit() {
        this._elForm.dispatchEvent(new Event('before-send'));
    
        if (this._isCheckValidationOnClient) {
            if (!this._checkValidity()) {
                const elError = this._elForm.querySelector('.is-error');
                if (elError) {
                    elError.focus();
                }
                return;
            }
        }
    
        const submitBtn = this._elForm.querySelector('[type="submit"]');
        const submitWidth = submitBtn.getBoundingClientRect().width;
        const submitHeight = submitBtn.getBoundingClientRect().height;
    
        submitBtn.textContent = '';
        submitBtn.disabled = true;
        submitBtn.style.width = `${submitWidth}px`;
        submitBtn.style.height = `${submitHeight}px`;
    
        const formError = this._elForm.querySelector('.form-error');
        if (formError) {
            formError.classList.add('form-error_hide');
        }
    
        // Отправляем на WordPress AJAX
        const formData = this._getFormData();
        formData.append('action', 'form_feedback');
        formData.append('page_title', document.title); // ✅ Добавлено
        formData.append('page_url', window.location.href); // ✅ Добавлено
        
        // Добавляем UTM-метки
        const utmData = getUtmData();
        Object.keys(utmData).forEach(key => {
            formData.append(key, utmData[key]);
        });
    
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.responseType = 'json';
    
        xhr.onload = () => {
            submitBtn.textContent = this._submitText;
            submitBtn.disabled = false;
            submitBtn.style.width = '';
            submitBtn.style.height = '';
    
            if (xhr.status == 200) {
                this._successXHR(xhr.response);
            } else {
                this._errorXHR();
            }
        };
    
        xhr.onerror = () => {
            submitBtn.textContent = this._submitText;
            submitBtn.disabled = false;
            submitBtn.style.width = '';
            submitBtn.style.height = '';
            this._errorXHR();
        };
    
        xhr.send(formData);
    }

    // Инициализация
    _init() {
        this._submitText = this._elForm.querySelector('[type="submit"]').textContent;
        this._addEventListener();
    }

    // Добавляем обработчики событий
    _addEventListener() {
        this._elForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this._onSubmit();
        });

        this._elForm.addEventListener('click', (e) => {
            const target = e.target;
            if (target.closest('.form-captcha__refresh')) {
                e.preventDefault();
                this._reloadCaptcha();
            }
        });
    }

    // Сброс формы
    reset() {
        const formError = this._elForm.querySelector('.form-error');
        if (formError) {
            formError.classList.add('form-error_hide');
        }
        this._elForm.reset();
        this._elForm.querySelectorAll('input, textarea').forEach(el => {
            this._setStateValidation(el);
        });
        this._elForm.querySelectorAll(".icon-check.complete").forEach(el => {
            el.classList.remove("complete");
        });

        if (document.querySelector('[name="captcha"]')) {
            this._reloadCaptcha();
        }
    }
}

// ============================================================
// Вспомогательные функции
// ============================================================

// Функция получения UTM-меток из URL
function getUtmData() {
    const urlParams = new URLSearchParams(window.location.search);
    const utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    const utmData = {};
    
    utmFields.forEach(field => {
        const value = urlParams.get(field);
        if (value) {
            utmData[field] = value;
        }
    });
    
    return utmData;
}

// ============================================================
// Инициализация
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    // 1. Инициализация формы в подвале (работает на всех страницах)
    const feedbackForm = document.getElementById('feedback-form');
    if (feedbackForm) {
        ItcSubmitForm.getOrCreateInstance(feedbackForm);
    }

    // 2. Инициализация калькулятора (только на главной)
    initCalculator();

    // 3. Инициализация hero-формы телефона (только на главной)
    initPhoneForm();
});

// ============================================================
// Калькулятор (6 шагов)
// ============================================================
function initCalculator() {
    const elementWithClasses = document.querySelector('.feedback-submit-btn.cw2-last-step-show');
    if (!elementWithClasses) return;

    elementWithClasses.style.display = 'none';

    const button = document.createElement('button');
    button.textContent = 'Рассчитать стоимость';
    button.className = 'formSelectSubmit cw2-last-step-show';
    elementWithClasses.parentNode.insertBefore(button, elementWithClasses.nextSibling);

    button.addEventListener('click', function(event) {
        event.preventDefault();
    
        const form = document.querySelector('#homePageFormSelect');
        const phoneInput = document.getElementById('inputFormSelect');
        const resultBlock = document.getElementById('formSelectResult');
        
        if (!form || !phoneInput || !resultBlock) return;
    
        // Валидация телефона
        const phoneValidation = "+7 " + phoneInput.value;
        if (!/^(?:\+7|8) \(\d{3}\) \d{3}-\d{2}-\d{2}$/.test(phoneValidation)) {
            resultBlock.textContent = 'Телефон введен неверно';
            resultBlock.style.display = 'block';
            resultBlock.style.color = 'red';
            return;
        }
    
        // Собираем данные
        const formData = new FormData(form);
        formData.append('action', 'form_calculator');
        formData.append('page_title', document.title); // ✅ Добавлено
        formData.append('page_url', window.location.href); // ✅ Добавлено
        
        // Добавляем UTM-метки
        const utmData = getUtmData();
        Object.keys(utmData).forEach(key => {
            formData.append(key, utmData[key]);
        });
    
        // Отправляем через XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.status === 'success') {
                        resultBlock.textContent = 'Форма отправлена';
                        resultBlock.style.display = 'block';
                        resultBlock.style.color = 'green';
                    } else {
                        resultBlock.textContent = data.message || 'Ошибка отправки';
                        resultBlock.style.display = 'block';
                        resultBlock.style.color = 'red';
                    }
                } catch (e) {
                    resultBlock.textContent = 'Ошибка обработки ответа';
                    resultBlock.style.display = 'block';
                    resultBlock.style.color = 'red';
                }
            } else {
                resultBlock.textContent = 'Ошибка сервера';
                resultBlock.style.display = 'block';
                resultBlock.style.color = 'red';
            }
        };
        
        xhr.onerror = function() {
            resultBlock.textContent = 'Ошибка сети';
            resultBlock.style.display = 'block';
            resultBlock.style.color = 'red';
        };
        
        xhr.send(formData);
    });
}

// ============================================================
// Форма телефона (hero-секция)
// ============================================================
function initPhoneForm() {
    // Проверяем наличие hero-формы
    const phoneInput = document.getElementById('phoneInput');
    if (!phoneInput) {
        return; // Нет hero-формы — выходим
    }
    
    const buttonPhone = document.querySelector('.btn.btn-1.feedback-submit-btn');
    if (!buttonPhone) return;

    buttonPhone.style.display = 'none';

    const newButtonPhone = document.createElement('button');
    newButtonPhone.textContent = 'Рассчитать стоимость';
    newButtonPhone.classList.add('btn', 'btn-1', 'formSelectSubmit');
    buttonPhone.parentNode.insertBefore(newButtonPhone, buttonPhone.nextSibling);

    newButtonPhone.addEventListener('click', function(event) {
        event.preventDefault();
        openFormPhone();
    });
}

function openFormPhone() {
    const phoneInput = document.getElementById("phoneInput");
    if (!phoneInput) return;
    
    const blockResult = document.getElementById("formPhoneResult");
    if (!blockResult) return;
    
    var phone = phoneInput.value;
    
    // Валидация
    var phoneValidation = "+7 " + phone;
    if (!/^(?:\+7|8) \(\d{3}\) \d{3}-\d{2}-\d{2}$/.test(phoneValidation)) {
        blockResult.textContent = "Телефон введен неверно";  
        blockResult.style.display = 'block';
        blockResult.style.color = 'red';
        return;
    }
    
    // Добавляем UTM-метки
    var utmData = getUtmData();
    var ajaxData = {
        action: 'formPhone',
        phone: phone,
        page_title: document.title, // ✅ Добавлено
        page_url: window.location.href // ✅ Добавлено
    };
    Object.assign(ajaxData, utmData);
    
    $.ajax({
        url: "/wp-admin/admin-ajax.php",
        data: ajaxData,
        type: 'POST',
        success: function(response) {
            blockResult.textContent = "Сообщение отправлено";  
            blockResult.style.display = 'block';
            blockResult.style.color = 'green';
        },
        error: function(data) {
            blockResult.textContent = "Ошибка сервера";  
            blockResult.style.display = 'block';
            blockResult.style.color = 'red';
        }
    });
}
