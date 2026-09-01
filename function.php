/**
 * WordPress Telegram & Bitrix24 Connector
 *
 * @author    Ivan Voitkov | Argon Studio (https://argon-studio.ru/)
 * @copyright 2026 Ivan Voitkov
 * @license   MIT License
 * @link      https://github.com/Argonstudio/wordpress-telegram-bitrix24-connector/
 */

$toEmail = 'mail@example.com';

/* Форма отправки телефона */
add_action('wp_ajax_formPhone', 'formPhone_callback');
add_action('wp_ajax_nopriv_formPhone', 'formPhone_callback');

function formPhone_callback(){
    $phone = sanitize_text_field($_POST["phone"]);
    
    $messageBody = "
Сообщение с формы: Узнайте стоимость демонтажа
Телефон: +7 {$phone}
";
    
    // Адресат почты
    global $toEmail;
    
    $headers  = 'MIME-Version: 1.0' . "\r\n"; 
    $headers .= 'Content-type: text/plain; charset=utf-8' . "\r\n"; 
    
    // Отсылаем письмо
    if(mail($toEmail, 'Заявка на услуги демонтирования', $messageBody, $headers)) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Ошибка отправки письма']);
    }
    
    // Отправка в Telegram
    slomcom_send_telegram($messageBody);
    
    // Отправка в Битрикс24
    $utm_data = slomcom_get_utm_data();
    $bitrix_sent = slomcom_send_bitrix24(
        'Клиент',
        $phone,
        'Заявка с формы в шапке (только телефон)',
        'Форма в шапке(с телефоном)',
        $utm_data
    );
    
    wp_die();
}

/* Форма "Задать вопрос" (подвал) */
add_action('wp_ajax_form_feedback', 'form_feedback_callback');
add_action('wp_ajax_nopriv_form_feedback', 'form_feedback_callback');

function form_feedback_callback() {
    $data = [
      'errors' => [],
      'form' => [],
      'result' => 'success'
    ];

    // валидация name
    if (!empty($_POST['name'])) {
      $data['form']['name'] = htmlspecialchars($_POST['name']);
      if (mb_strlen($data['form']['name'], 'UTF-8') > 255) {
        $data['result'] = 'error';
        $data['errors']['name'] = 'Значение не должно превышать 255 символов';
      }
    } else {
      $data['result'] = 'error';
      $data['errors']['name'] = 'Поле обязательно для заполнения';
    }

    // валидация телефона
    if (!empty($_POST['phone'])) {
      $data['form']['phone'] = "+7 ".$_POST['phone'];
      if (!preg_match('/^(?:\+7|8) \(\d{3}\) \d{3}-\d{2}-\d{2}$/', "+7 ".$_POST['phone'])) {
        $data['result'] = 'error';
        $data['errors']['phone'] = 'Телефон введен некорректно(php)';
      }
    } else {
      $data['result'] = 'error';
      $data['errors']['phone'] = 'Поле обязательно для заполнения';
    }

    // валидация message
    if (!empty($_POST['message'])) {
      $data['form']['message'] = htmlspecialchars($_POST['message']);
      if (mb_strlen($data['form']['message'], 'UTF-8') > 4096) {
        $data['result'] = 'error';
        $data['errors']['message'] = 'Значение не должно превышать 4096 символов';
      }
    } else {
      $data['result'] = 'error';
      $data['errors']['message'] = 'Поле обязательно для заполнения';
    }

    // валидация agree
    if ($_POST['agree'] == 'true') {
      $data['form']['agree'] = true;
    } else {
      $data['result'] = 'error';
      $data['errors']['agree'] = 'Необходимо установить этот флажок';
    }

    if ( $data['result'] == 'success' ) {
      
      global $toEmail;
      
      $theme = "Форма на PHP И JavaScript";
      
      $message = "Имя: ".$data['form']['name']."<br>";
      $message .= "Номер телефона: ".$data['form']['phone']."<br>"; 
      $message .= "Сообщение: ".$data['form']['message']."<br>"; 
        
      $tg_message = "Имя: ".$data['form']['name']."\n";
      $tg_message .= "Номер телефона: ".$data['form']['phone']."\n";
      $tg_message .= "Сообщение: ".$data['form']['message'];
      
      $headers  = 'MIME-Version: 1.0' . "\r\n"; 
      $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n"; 
      
      mail($toEmail, $theme, $message, $headers); 
      
      // Отправка в Telegram
      slomcom_send_telegram($tg_message);
      
      // Отправка в Битрикс24
      $utm_data = slomcom_get_utm_data();
      $bitrix_sent = slomcom_send_bitrix24(
          $data['form']['name'],
          $data['form']['phone'],
          $data['form']['message'],
          'Форма с каптчей в подвале',
          $utm_data
      );
      
    }

    echo json_encode($data);
    wp_die();
}

/* Форма калькулятора (6 шагов) */
add_action('wp_ajax_form_calculator', 'form_calculator_callback');
add_action('wp_ajax_nopriv_form_calculator', 'form_calculator_callback');

function form_calculator_callback() {
    // Получение POST-данных
    $typeHouse = $_POST['p32'];
    $workTypes = isset($_POST['p33']) ? implode(', ', $_POST['p33']) : '';
    $squareMeters = $_POST['p34'];
    $trashRemoval = ($_POST['p35'] == '1') ? 'Нет' : 'Да';
    $objectLocation = $_POST['p36'];
    $demolitionDate = $_POST['p37'];
    $phoneNumber = $_POST['p38'];

    // Формируем тело письма
    $messageBody = "
Тип дома: {$typeHouse}
Необходимые работы: {$workTypes}
Площадь помещения: {$squareMeters} м²
Вывоз мусора: {$trashRemoval}
Местоположение объекта: {$objectLocation}
Планируемый срок демонтажа: {$demolitionDate}
Контактный телефон: {$phoneNumber}
";

    global $toEmail;
    
    $headers  = 'MIME-Version: 1.0' . "\r\n"; 
    $headers .= 'Content-type: text/plain; charset=utf-8' . "\r\n"; 
    
    // Отсылаем письмо
    if(mail($toEmail, 'Заявка на услуги демонтирования', $messageBody, $headers)) {
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Ошибка отправки письма']);
    }

    // Отправка в Telegram
    slomcom_send_telegram($messageBody);
    
    // Отправка в Битрикс24
    $utm_data = slomcom_get_utm_data();
    
    $bitrix_comment = "Тип дома: {$typeHouse}\n";
    $bitrix_comment .= "Необходимые работы: {$workTypes}\n";
    $bitrix_comment .= "Площадь помещения: {$squareMeters} м²\n";
    $bitrix_comment .= "Вывоз мусора: {$trashRemoval}\n";
    $bitrix_comment .= "Местоположение объекта: {$objectLocation}\n";
    $bitrix_comment .= "Планируемый срок демонтажа: {$demolitionDate}";
    
    $bitrix_sent = slomcom_send_bitrix24(
        'Клиент',
        $phoneNumber,
        $bitrix_comment,
        'Форма калькулятор 6 вопросов',
        $utm_data
    );

    wp_die();
}

// Вспомогательная функция отправки в Telegramm
function slomcom_send_telegram($message) {
    $token = defined('SLOMCOM_TG_TOKEN') ? SLOMCOM_TG_TOKEN : '';
    $chat_ids = defined('SLOMCOM_TG_CHAT_IDS') ? explode(',', SLOMCOM_TG_CHAT_IDS) : [];

    if (empty($token) || empty($chat_ids)) {
        return false;
    }

    foreach ($chat_ids as $chat_id) {
        $chat_id = trim($chat_id);
        if (empty($chat_id)) {
            continue;
        }

        $url = sprintf(
            'https://api.telegram.org/bot%s/sendMessage?chat_id=%s&parse_mode=html&text=%s',
            $token,
            $chat_id,
            urlencode($message)
        );

        wp_remote_get($url, ['timeout' => 10]);
    }

    return true;
}

// Вспомогательная функция отправки в Битрикс24
function slomcom_send_bitrix24($name, $phone, $comment, $source_description, $utm_data = []) {
    $webhook_url = defined('SLOMCOM_BITRIX_WEBHOOK') ? SLOMCOM_BITRIX_WEBHOOK : '';
    
    if (empty($webhook_url)) {
        error_log('Bitrix24: Webhook не настроен');
        return false;
    }
    
    $assigned_by_id = defined('SLOMCOM_BITRIX_ASSIGNED_BY_ID') ? SLOMCOM_BITRIX_ASSIGNED_BY_ID : 19;
    
    // Получаем URL страницы из POST или REFERER
    $page_url = isset($_POST['page_url']) ? sanitize_text_field($_POST['page_url']) : '';
    if (empty($page_url)) {
        $page_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    }
    if (empty($page_url)) {
        $page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
    
    $page_path = parse_url($page_url, PHP_URL_PATH);
    if (empty($page_path)) {
        $page_path = '/';
    }
    
    // Получаем заголовок страницы из POST
    $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';
    if (empty($page_title)) {
        // Пробуем получить заголовок из URL
        $page_title = basename($page_path);
        if ($page_title == '' || $page_title == '/') {
            $page_title = 'Главная';
        } else {
            // Преобразуем slug в читаемый вид
            $page_title = ucwords(str_replace(['-', '_'], ' ', $page_title));
        }
    }
    
    $phone_digits = preg_replace('/\D+/', '', $phone);
    
    if (strlen($phone_digits) < 10) {
        error_log('Bitrix24: Телефон слишком короткий');
        return false;
    }
    
    $name = trim($name);
    if (empty($name)) {
        $name = 'Клиент';
    }
    
    $title = "slomcom.ru / {$source_description} / {$page_title}";
    if (mb_strlen($title) > 240) {
        $title = mb_substr($title, 0, 240);
    }
    
    $comments = [];
    $comments[] = 'Сайт: slomcom.ru';
    $comments[] = 'Форма: ' . $source_description;
    $comments[] = 'Страница заявки: ' . $page_title . ' (' . $page_path . ')';
    $comments[] = 'Заголовок страницы: ' . $page_title;
    $comments[] = 'Полный URL: ' . $page_url;
    
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $comments[] = 'Referrer: ' . ($referrer !== '' ? $referrer : 'нет');
    
    $comments[] = 'Имя: ' . $name;
    $comments[] = 'Телефон: ' . $phone_digits;
    
    if (!empty($comment)) {
        $comments[] = 'Сообщение: ' . $comment;
    }
    
    if (!empty($utm_data)) {
        $comments[] = 'UTM текущие: ' . json_encode($utm_data, JSON_UNESCAPED_UNICODE);
    }
    
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ($user_agent !== '') {
        $comments[] = 'User-Agent: ' . $user_agent;
    }
    
    $comments[] = 'Время: ' . date('c');
    
    $fields = [
        'TITLE' => $title,
        'NAME' => $name,
        'ASSIGNED_BY_ID' => $assigned_by_id,
        'SOURCE_ID' => 'WEB',
        'SOURCE_DESCRIPTION' => "slomcom.ru / {$source_description} / {$page_title}",
        'OPENED' => 'Y',
        'COMMENTS' => implode("\n", $comments),
        'PHONE' => [
            [
                'VALUE' => $phone_digits,
                'VALUE_TYPE' => 'WORK'
            ]
        ],
    ];
    
    if (!empty($utm_data['utm_source'])) {
        $fields['UTM_SOURCE'] = $utm_data['utm_source'];
    }
    if (!empty($utm_data['utm_medium'])) {
        $fields['UTM_MEDIUM'] = $utm_data['utm_medium'];
    }
    if (!empty($utm_data['utm_campaign'])) {
        $fields['UTM_CAMPAIGN'] = $utm_data['utm_campaign'];
    }
    if (!empty($utm_data['utm_content'])) {
        $fields['UTM_CONTENT'] = $utm_data['utm_content'];
    }
    if (!empty($utm_data['utm_term'])) {
        $fields['UTM_TERM'] = $utm_data['utm_term'];
    }
    
    $url = $webhook_url . 'crm.lead.add.json';
    
    $response = wp_remote_post($url, [
        'body' => json_encode(['fields' => $fields]),
        'headers' => [
            'Content-Type' => 'application/json; charset=utf-8',
        ],
        'timeout' => 15,
    ]);
    
    if (is_wp_error($response)) {
        error_log('Bitrix24 error: ' . $response->get_error_message());
        return false;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['result']) && $body['result']) {
        error_log('Bitrix24: Лид создан, ID: ' . $body['result']);
        return true;
    }
    
    error_log('Bitrix24 API error: ' . print_r($body, true));
    return false;
}

// Получение UTM-меток
function slomcom_get_utm_data() {
    $utm_fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    $utm_data = [];
    
    foreach ($utm_fields as $field) {
        if (isset($_POST[$field]) && !empty($_POST[$field])) {
            $utm_data[$field] = sanitize_text_field($_POST[$field]);
        } elseif (isset($_COOKIE[$field]) && !empty($_COOKIE[$field])) {
            $utm_data[$field] = sanitize_text_field($_COOKIE[$field]);
        } elseif (isset($_GET[$field]) && !empty($_GET[$field])) {
            $utm_data[$field] = sanitize_text_field($_GET[$field]);
        }
    }
    
    return $utm_data;
}
