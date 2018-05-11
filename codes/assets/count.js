$.fn.mailingCode.codes.set('count', function (tagName) {
  let counters = {}
  return $.extend(new $.fn.mailingCode.Code(tagName, {
    comment: 'Количество элементов',
    help: 'Используйте этот шордкод для вывода количества извлекаемых элементов. Например, количества записей новостей. Если количество элементов будет меньше 1, то рассылка не сможет быть выполнена.',
    option: true,
    param: false,
    inline: true,
  }), {
    option: $.extend(new $.fn.mailingCode.Attribute('option', {
      title: 'Счетчик элементов типа',
      type: $('<select>')
    }), {
      getInput: function (id) {
        let self = this
        let input = self.config.type.clone()
        for (let value in counters) {
          if (counters.hasOwnProperty(value)) {
            input.append($('<option>', {value: value}).text(counters[value].title))
          }
        }
        input.find('[value="' + self.getValue() + '"]').attr('selected', 'selected');
        return $('<div>', {
          class: 'form-group',
        }).append($('<label>', {
          for: id + '-' + self.name
        }).text(self.config.title ? self.config.title : self.name)).append(input.attr({
          id: id + '-' + self.name,
          class: 'form-control',
        }).change(function () {
          self.setValue($(this).val())
        }).change())
      }
    }),
    counter: function (name, title) {
      counters[name] = {
        title: title
      }
    },
    preview: function () {
      let option = this.option.getValue()
      if (counters.hasOwnProperty(option)) {
        option = counters[option].title
      }
      return $('<span>').text('{{ Количество записей типа "' + option + '" }}')
    },
  })
})