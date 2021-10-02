//убираем type для валидации
AddEventHandler("main", "OnEndBufferContent", "delete_type");

function delete_type(&$content)
{
    $content = str_replace(' type="text/javascript"', '', $content);
}
