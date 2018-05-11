$.fn.mailingCode.codes.set('test', function (tagName) {
  let code = $.extend(new $.fn.mailingCode.Code(tagName, {
    comment: 'Простой тестовый блок',
    help: 'Используйте этот шордкод для создания блоков закрытых тегом.',
    optionConfig: {
      title: 'Имя тега',
      default: 'div'
    },
  }), {
    getOptions: function () {
      let string = ''
      this.options.each(function (key, value) {
        value = value.getValue()
        if (value) {
          if (value === true) {
            string = string + ' ' + key
          } else {
            string = string + ' ' + key + '=' + decodeURIComponent(value)
          }
        }
      })
      return string
    },
    preview: function () {
      return $('<span>').text('<' + this.option.getValue() + this.getOptions() + '>' + this.param.getValue() + '</' + this.option.getValue() + '>')
    }
  })
  code.options.set('class', new $.fn.mailingCode.Attribute('class', {
    title: 'CSS Класс'
  }))
  return code
})