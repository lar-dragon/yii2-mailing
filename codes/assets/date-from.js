$.fn.mailingCode.codes.set('date-from', function (tagName) {
  return $.extend(new $.fn.mailingCode.Code(tagName, {
    comment: 'Дата от',
    help: 'Используйте этот шордкод для вывода даты с предыдущей расслки (Поле "Запущено").',
    option: false,
    param: false,
  }), {
    preview: function () {
      return $('<span>').text('{{ Дата от }}')
    }
  })
})