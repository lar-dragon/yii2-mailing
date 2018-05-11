$.fn.mailingCode.codes.set('list', function (tagName) {
  let lists = {}
  let code = $.extend(new $.fn.mailingCode.Code(tagName, {
    comment: 'Список элементов',
    help: 'Используйте этот шордкод для вывода списка необходимых элементов. Если количество элементов меньше 1, то рассылка не сможет быть выполнена.',
    option: true,
    param: false,
  }), {
    option: $.extend(new $.fn.mailingCode.Attribute('option', {
      title: 'Перечисление элементов типа',
      type: $('<select>')
    }), {
      getInput: function (id) {
        let self = this
        let input = self.config.type.clone()
        for (let value in lists) {
          if (lists.hasOwnProperty(value)) {
            input.append($('<option>', {value: value}).text(lists[value].title))
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
          let oldValue = self.getValue()
          let newValue = $(this).val()
          if (oldValue !== newValue) {
            self.setValue(newValue)
            $('#' + id + '-items').val('').change()
          }
        }).change())
      }
    }),
    list: function (name, title, url) {
      lists[name] = {
        title: title,
        url: url,
      }
    },
    preview: function () {
      let option = this.option.getValue()
      if (lists.hasOwnProperty(option)) {
        option = lists[option].title
      }
      return $('<span>').text('{{ Перечисление "' + option + '" }}')
    }
  })
  code.options.set('items', $.extend(new $.fn.mailingCode.Attribute('items', {
    title: 'Отдельные элементы',
  }), {
    getInput: function (id) {
      let self = this
      let loader = $('<span>', {
        class: 'fa fa-refresh fa-spin hidden',
        'aria-hidden': 'true'
      }).css({
        position: 'relative',
        float: 'right',
        top: '35px',
        right: '10px'
      })
      let input = self.config.type.clone().attr({
        id: id + '-' + self.name,
        class: 'form-control',
      }).val(self.getValue()).change(function () {
        self.setValue($(this).val())
      })
      setTimeout(function () {
        input.tagit({
          singleFieldDelimiter: ';',
          removeConfirmation: true,
          showAutocompleteOnFocus: true,
          beforeTagAdded: function (event, ui) {
            return $.isNumeric(ui.tagLabel) && ui.tagLabel % 1 === 0
          },
          autocomplete: {
            delay: 0,
            minLength: 2,
            open: function () {
              loader.addClass('hidden')
              $('.ui-menu').css({
                'max-width': input.width(),
                'z-index': 2300
              });
            },
            close: function () {
              $('.ui-menu').css({
                'max-width': null,
                'z-index': null
              });
            },
            source: function (request, response) {
              let list = $('#' + id + '-option').val()
              if (!lists.hasOwnProperty(list)) {
                return;
              }
              loader.removeClass('hidden')
              $.post(lists[list].url, {
                search: request,
                selected: input.val().split(';'),
                list: list
              }).then(function (data, status) {
                if (status === 'success' && Array.isArray(data) && data.length > 0) {
                  response(data)
                }
              })
            }
          }
        })
      }, 200)
      return $('<div>', {
        class: 'form-group',
      }).append($('<label>', {
        for: id + '-' + self.name
      }).text(self.config.title ? self.config.title : self.name)).append(loader).append(input).append($('<p>', {
        class: 'help-block'
      }).text('Введите тут отдельные записи для вывода. Если это поле не задано, будут выбраны все записи за период рассылки.'))
    }
  }))
  return code
})