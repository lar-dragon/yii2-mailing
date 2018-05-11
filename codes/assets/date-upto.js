$.fn.mailingCode.codes.set('date-upto', function (tagName) {
  return $.extend(new $.fn.mailingCode.Code(tagName, {
    comment: 'Дата до',
    help: 'Используйте этот шордкод для вывода даты выполнения расслки (Поле "Запланировано").',
    option: false,
    param: false,
    inline: true,
  }), {
    preview: function () {
      return $('<span>').text('{{ Дата до }}')
    }
  })
})