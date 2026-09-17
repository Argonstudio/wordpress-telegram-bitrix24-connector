<?php

/**
 * WordPress Telegram & Bitrix24 Connector
 *
 * This file is a derivative work based on the "Feedback Form" 
 * by Alexander Maltsev (ITChief).
 * Original source: https://github.com/itchief/feedback-form
 * Original license: MIT
 *
 * @author    Ivan Voitkov | Argon Studio (https://argon-studio.ru/)
 * @copyright 2026 Ivan Voitkov
 * @license   MIT License
 * @link      https://github.com/Argonstudio/wordpress-telegram-bitrix24-connector/
 */

?>

<div class="form-container form__wrapper">
        
              <!-- Форма обратной связи -->
              <form id="feedback-form" action="form-processing.php" enctype="multipart/form-data" novalidate>
                  
                <div class="form-group feedback_form_param29 flex-column-reverse">
                    
                    <input id="name" type="text" name="name" class="form-control" value="" placeholder="Введите текст">
                    <div class="error-feedback"></div>
                    <p class="form-item-title text-small text-silver flex-align-center flex-start">Имя:</p>
                    
                </div>
                  
                 <!-- Телефон пользователя --> 
                 <div class="form-group feedback_form_param30 flex-column-reverse">
                    <p class="text-small form-item-error errors error_p30" style="display:none"></p>
                    
                    <div class="flex-start js_select_phone">
                        <div class="select select_phone margin-right-10 hide_arrow">
                            <div class="SumoSelect sumo_p30_cod" tabindex="0"><select name="p30_cod" class="SumoUnder" tabindex="-1">
                                <option value="+7" data-mask="(000) 000-00-00">+7</option></select>
                                <p class="CaptionCont SelectBox" title=" +7"><span> +7</span></p>
                                
                            </div>
                        </div>
                        
                        <input id="phone" type="tel" data-mask="(000) 000-00-00" name="phone" required="required" class="form-control" value="" placeholder="Телефон">
                        
                    </div>
                    
                    <p class="form-item-title text-small text-silver flex-align-center flex-start">Телефон:<span class="form-item-required">•</span></p>
                    <div class="error-feedback"></div>
                    
                </div> 
                
                <!-- Сообщение пользователя -->
                <div class="form-group feedback_form_param31 flex-column-reverse">
                    <textarea id="message" name="message" class="form-control" rows="3"
                    placeholder="Ваш вопрос"></textarea>
                    
                      <div class="error-feedback"></div>
                    
                    <p class="form-item-title text-small text-silver flex-align-center flex-start">Ваш вопрос:</p>
                    
                </div>
                
        
                <!-- Капча -->
                <div class="form-group form-captcha">
                    
                  <img class="form-captcha__image" src="https://slomcom.ru/wp-content/themes/slomcom/assets/captcha/captcha.php" data-src="https://slomcom.ru/wp-content/themes/slomcom/assets/captcha/captcha.php"
                    width="132" height="46" alt="Капча">
                    
                  <div class="form-captcha__refresh">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16">
                      <path fill="currentColor"
                        d="M440.65 12.57l4 82.77A247.16 247.16 0 0 0 255.83 8C134.73 8 33.91 94.92 12.29 209.82A12 12 0 0 0 24.09 224h49.05a12 12 0 0 0 11.67-9.26 175.91 175.91 0 0 1 317-56.94l-101.46-4.86a12 12 0 0 0-12.57 12v47.41a12 12 0 0 0 12 12H500a12 12 0 0 0 12-12V12a12 12 0 0 0-12-12h-47.37a12 12 0 0 0-11.98 12.57zM255.83 432a175.61 175.61 0 0 1-146-77.8l101.8 4.87a12 12 0 0 0 12.57-12v-47.4a12 12 0 0 0-12-12H12a12 12 0 0 0-12 12V500a12 12 0 0 0 12 12h47.35a12 12 0 0 0 12-12.6l-4.15-82.57A247.17 247.17 0 0 0 255.83 504c121.11 0 221.93-86.92 243.55-201.82a12 12 0 0 0-11.8-14.18h-49.05a12 12 0 0 0-11.67 9.26A175.86 175.86 0 0 1 255.83 432z">
                      </path>
                    </svg>
                  </div>
                  
                  <div class="form-group form-captcha__input">
                      
                    <label for="captcha" class="control-label d-none">Код, показанный на изображении</label>
                    <input type="text" name="captcha" maxlength="6" required="required" id="captcha"
                      class="form-control captcha" placeholder="******" autocomplete="off" value="">
                    <div class="error-feedback"></div>
                    
                  </div>
                  
                </div>
                
        
                <!-- Пользовательское солашение -->
                <div class="form-group form-agree form-check">
                    
                  <input class="form-check-input" type="checkbox" name="agree" id="agree" required="required" value="true">
                  
                  <label class="form-check-label" for="agree">
                      
                      Отправляя форму, я даю согласие на <a href="<?= get_home_url() ?>/politika-konfidentsialnosti/">обработку персональных данных</a> и соглашаюсь 
                      с <a href="<?= get_home_url() ?>/politika-konfidentsialnosti/">политикой конфиденциальности.</a>
                      
                  </label>
                  
                  <div class="error-feedback"></div>
                  
                </div>
        
                <!-- Сообщение об ошибке -->
                <div class="form-error form-error_hide">Исправьте данные и отправьте форму ещё раз.</div>
        
                <!-- Кнопка для отправки формы на сервер -->
                <div class="form-submit">
                  <button class="btn btn-1 feedback-submit-btn" type="submit">Рассчитать стоимость</button>
                </div>
        
              </form>
        
              <!-- Сообщение об успешной отправки формы -->
              <div class="form-success form-success_hide">
                <div class="form-success__message">Форма успешно отправлена. Нажмите <button type="button"
                    class="form-success__btn">здесь</button>, если нужно отправить ещё одну форму.</div>
              </div>
        
        </div>
