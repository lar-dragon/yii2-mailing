$.fn.mailingCode.codes.set('links', function (tagName) {
  return $.extend(new $.fn.mailingCode.Code(tagName, {
    comment: 'Блок ссылок',
    help: 'Используйте этот шордкод в конце письма для организации блока ссылок.',
    option: false,
    param: false,
  }), {
    preview: function () {
      return $('<span>').text('{{ Блок ссылок }}')
    }
  })
})