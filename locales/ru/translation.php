<?php
if (ISINCLUDED != '1') {
    header('HTTP/1.0 403 Forbidden');
    die('Forbidden!');
}

// List of all localization constants
$translation = array(
    '%s ago' => '%s назад',
    'Administrate' => 'Администрирование',
    'Author\'s IP address:' => 'IP-адрес автора:',
    'Back to main page' => 'Вернуться на главную страницу',
    'Cannot connect to data storage, check config!' => 'Невозможно соединиться с хранилищем данных, проверьте конфигурацию!',
    'Cannot create data storage, check config!' => 'Невозможно создать хранилище данных, проверьте конфигурацию!',
    'Checking data storage connection.' => 'Проверяю соединение с хранилищем данных.',
    "Congratulations, your Pastebin has now been installed!\nThis message will expire in 30 minutes!" => "Поздравляю, Ваш Pastebin установлен!\nСрок жизни этого сообщения истечёт через 30 минут!",
    'Connection established!' => 'Соединение установлено!',
    'Control:' => 'Управление:',
    'Create' => 'Создать',
    'Data %s has been deleted!' => 'Данные %s были удалены!',
    'Data storage is unavailable - check config!' => 'Хранилище данных недоступно - проверьте конфигурацию!',
    'Delete data' => 'Удалить данные',
    'Edit this post:' => 'Редактировать этот пост:',
    'Editing' => 'Редактирование',
    'Enabled' => 'Включено',
    'Expand' => 'Развернуть',
    'Expiration' => 'Срок жизни',
    'Expires:' => 'Срок жизни:',
    'Fill out the form with data you wish to store online. You will be given an unique address to access your content that can be sent over IM/chat/(micro)blog for online collaboration. The following services have been made available by the administrator of this server:' => 'Заполните поля формы данными, которые Вы хотите опубликовать онлайн. Вы получите уникальный адрес для доступа к Вашему контенту, который затем может быть отправлен в IM/чате/(микро)блоге для взаимодействия в Сети. Администратором этого Pastebin были сделаны доступными следующие сервисы:',
    'Go to main page of installed Pastebin!' => 'Перейти на главную страницу установленного Pastebin',
    'How to use' => 'Как пользоваться',
    'ID:' => 'ID:',
    'If you want to put a message up asking if the user wants to continue, add an "!" suffix to your URL.' => 'Если Вы хотите спросить пользователя, желает ли он продолжить, добавьте "!" к Вашему URL.',
    'If you wish to view the data' => 'Если Вы хотите увидеть данные',
    'Installing Pastebin' => 'Устанавливаю Pastebin',
    'It is recommended to adjust directory permissions' => 'Рекомендуется скорректировать права на директорию',
    'Just a sec!' => 'Минуточку!',
    'Just paste your text, sourcecode or log into the textarea below, add a name if you wish then submit the data.' => 'Просто вставьте Ваш текст, исходный код или лог в расположенное ниже текстовое поле, по желанию добавьте имя, а затем отправьте данные.',
    'Line highlighting' => 'Подсветка строк',
    'Missed required PHP %s extension.' => 'Отсутствует необходимое расширение PHP %s.',
    'Never' => 'Не установлен',
    'Note:' => 'Примечание:',
    'OK' => 'ОК',
    'PHP 5.4 or higher is required to run this pastebin. This version is %s' => 'Для запуска приложения требуется версия PHP 5.4 и выше. Текущая версия %s',
    'Password' => 'Пароль',
    'Password is not default!' => 'Пароль задан не по умолчанию!',
    'Password is still default!' => 'Оставлен пароль по умолчанию!',
    'Paste your text here:' => 'Вставьте сюда свой текст:',
    'Pastebin on %s' => 'Pastebin на %s',
    'Please don\'t just repost what has already been posted!' => 'Пожалуйста, не делайте простой перепост того, что уже было опубликовано!',
    'Please note that the owner of this Pastebin is not responsible for posted data.' => 'Пожалуйста, обратите внимание, что владелец этого Pastebin не несёт ответственности за размещённые данные.',
    'Post text' => 'Публикация текста',
    'Posted by:' => 'Опубликовано:',
    'Private' => 'Личное',
    'Public' => 'Общедоступное',
    'Quick password check.' => 'Быстрая проверка пароля.',
    'Quick salts check.' => 'Быстрая проверка солей.',
    'Raw' => 'Исходник',
    'Recent' => 'Последнее',
    'Salt strings are adequate!' => 'Строки солей адекватны!',
    'Salt strings are inadequate!' => 'Неадекватные строки солей!',
    'Show author\'s IP' => 'Показать IP автора',
    'Size:' => 'Объём:',
    'Something went wrong.' => 'Что-то пошло не так.',
    'Spambot detected!' => 'Обнаружен спамбот!',
    'Style' => 'Стиль',
    'Submit' => 'Отправить',
    'The size of data must be between %d bytes and %s' => 'Объём данных должен быть в диапазоне от %d байт до %s',
    'There was an error!' => 'Произошла ошибка!',
    'This data has either expired or doesn\'t exist!' => 'Эти данные либо просрочены, либо не существовали!',
    'This is a derivative of' => 'Это производное от',
    'To highlight lines, prefix them with' => 'Для подсветки строки начните её с',
    'URL to your data is' => 'Ваши данные доступны по ссылке',
    'Visibility' => 'Видимость',
    'Unable to create derivative post for the absent one!' => 'Невозможно создать пост, производный от несуществующего!',
    'Unable to create public derivative post for the private one!' => 'Невозможно создать общедоступный пост, производный от личного!',
    'Welcome!' => 'Здравствуйте!',
    'What to do' => 'Что делать',
    'Wrap' => 'Обернуть',
    'You are about to access a data that the author has marked as requiring confirmation to view.' => 'Вы собираетесь получить доступ к данным, которые автор пометил как требующие подтверждения для просмотра.',
    'Your data has been successfully recorded!' => 'Ваши данные были успешно записаны!',
    'Your name' => 'Ваше имя',
    'b Kb Mb' => 'байт Кб Мб',
    'click here' => 'кликните здесь',
    'in %s' => 'в течение %s',
    'more info' => 'подробнее',
);

// List of all language-specific functions
$translation_functions = array(
    'translate_time' => function($number, $units) {
                            $ru_units = array(
                                'seconds' => array( 0 => 'секунд',
                                                    1 => 'секунда',
                                                    2 => 'секунды', ),
                                'minutes' => array( 0 => 'минут',
                                                    1 => 'минута',
                                                    2 => 'минуты', ),
                                'hours' => array( 0 => 'часов',
                                                  1 => 'час',
                                                  2 => 'часа', ),
                                'days' => array( 0 => 'дней',
                                                 1 => 'день',
                                                 2 => 'дня', ),
                                'weeks' => array( 0 => 'недель',
                                                  1 => 'неделя',
                                                  2 => 'недели', ),
                                'years' => array( 0 => 'лет',
                                                  1 => 'год',
                                                  2 => 'года', ),
                            );
                            $result = $number . ' ' ;
                            if ((int) $number > 20) {
                                $number = substr($number, -1);
                            }
                            $number = (int) $number;
                            $form = 0;
                            if ($number === 1) {
                                $form = 1;
                            } elseif ($number > 1 && $number < 5) {
                                $form = 2;
                            }
                            return $result . $ru_units[$units][$form];
                        },
);
