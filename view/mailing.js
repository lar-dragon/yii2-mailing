(function ($) {

  let shordcode = /\[(?<tag>[^\s=\]]+)(=(?<option>[^\]\s]*))?(?<options>(\s+[^\s\]=]+(=[^\s\]]*)?)*)\s*\]((?<param>.*?)\[\/\k<tag>\])/g
  let option = /(?<option>[^\s\]=]+)(=(?<value>[^\s\]]+))?/g
  let name = /{(?<name>\w+)}/g
  let email = /^[a-zA-Zа-яА-Я0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Zа-яА-Я0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-zа-я](?:[a-zа-я0-9-]*[a-zа-я0-9])?\.)+[a-zа-я]{2}(?:[a-zа-я0-9-]*[a-zа-я0-9])?$/

  let exec = function (regexp, string) {
    let result = regexp.exec(string)
    regexp.lastIndex = 0 // reset exp
    return result
  }

  let Cache = function (config = {}) {
    this.config = $.extend({}, this.config, config)
    this.dump = this.config.read()
  }
  Cache.prototype = {
    dump: {},
    config: {
      alive: 0,
      read: function () {
        return {}
      },
      save: function (dump) {}
    },
    each: function (callback) {
      let timeout = this.config.alive > 0
        ? Date.now() - this.config.alive
        : 0
      for (let cachedKey in this.dump) {
        if (this.dump.hasOwnProperty(cachedKey) && this.dump[cachedKey].time > timeout) {
          callback(this.dump[cachedKey].key, this.dump[cachedKey].value)
        }
      }
    },
    cache: function (key) {
      return JSON.stringify({key: key})
    },
    has: function (key) {
      let cachedKey = this.cache(key)
      let timeout = this.config.alive > 0
        ? Date.now() - this.config.alive
        : 0
      return this.dump.hasOwnProperty(cachedKey) && this.dump[cachedKey].time > timeout && this.dump[cachedKey].key === key
    },
    get: function (key) {
      let cachedKey = this.cache(key)
      return this.dump[cachedKey].value
    },
    set: function (key, value) {
      let cachedKey = this.cache(key)
      this.dump[cachedKey] = {
        key: key,
        time: Date.now(),
        value: value
      }
      this.config.save(this.dump)
    }
  }

  let Attribute = function (name, config) {
    this.name = name
    this.config = $.extend({}, this.config, config)
    this.value = this.config.default
  }
  Attribute.prototype = {
    config: {
      title: '',
      default: '',
      type: $('<input>', {type: 'text'})
    },
    create: function (value) {
      let attribute = $.extend({}, this)
      attribute.value = value ? value : this.config.default;
      return attribute;
    },
    setValue: function (value) {
      this.value = value
    },
    getValue: function () {
      return this.value
    },
    getInput: function (id) {
      let self = this
      return $('<div>', {
        class: 'form-group',
      }).append($('<label>', {
        for: id + '-' + self.name
      }).text(self.config.title ? self.config.title : self.name)).append(self.config.type.clone().attr({
        id: id + '-' + self.name,
        class: 'form-control',
      }).val(self.getValue()).change(function () {
        self.setValue($(this).val())
      }))
    }
  }

  let Code = function (tag, config) {
    let self = this
    self.tag = tag
    self.config = $.extend({}, self.config, config)
    self.option = new Attribute('option', self.config.optionConfig) // значение
    self.options = new Cache({}) // атрибуты
    self.param = new Attribute('param', self.config.paramConfig) // тело
    self.config.pattern.replace(name, function (match) {
      let result = exec(name, match)
      if (!result) {
        return
      }
      if (
        ['param', 'option', 'options'].indexOf(result.groups.name) === -1
        && !self.options.has(result.groups.name)
      ) {
        self.options.set(result.groups.name, new Attribute(result.groups.name, {}))
      }
    })
  }
  Code.prototype = {
    config: {
      inline: false,
      help: '',
      pattern: '{param}',
      css: '',
      comment: '',
      option: true,
      optionConfig: {
        title: 'Параметр'
      },
      param: true,
      paramConfig: {
        title: 'Содержимое',
        type: $('<textarea>')
      },
    },
    encode: function (string) {
      return string.replace(' ', '%20').replace(']', '%5D')
    },
    decode: function (string) {
      return string.replace('%20', ' ').replace('%5D', ']')
    },
    create: function (param) {
      let code = $.extend({}, this)
      code.option = this.option.create('')
      code.param = this.param.create(param ? param :'')
      code.options = new Cache({})
      this.options.each(function (key, value) {
        code.options.set(key, value.create(''))
      })
      return code
    },
    toTag: function () {
      let self = this
      let string = self.tag
      let value = self.option.getValue()
      if (value && value !== true && self.config.option) {
        string = string + '=' + self.encode(value)
      }
      self.options.each(function (key, value) {
        value = value.getValue()
        if (value) {
          if (value === true) {
            string = string + ' ' + key
          } else {
            string = string + ' ' + key + '=' + self.encode(value)
          }
        }
      })
      let param = self.config.param ? self.param.getValue() : ''
      return '[' + string + ']' + param + '[/' + self.tag + ']'
    },
    fromTag: function (tag) {
      let self = this
      let result = exec(shordcode, tag)
      self.tag = result && result.groups.tag ? result.groups.tag : self.tag
      self.option.setValue(result && result.groups.option ? this.decode(result.groups.option) : '')
      self.param.setValue(result && result.groups.param ? result.groups.param : '')
      let options = result && result.groups.options ? result.groups.options : ''
      options.replace(option, function (match) {
        result = exec(option, match)
        if (!result) {
          return
        }
        if (!self.options.has(result.groups.option)) {
          self.options.set(result.groups.option, new Attribute(result.groups.option, {}))
        }
        self.options.get(result.groups.option).setValue(result.groups.value ? self.decode(result.groups.value) : '')
      })
    },
    preview: function () {
      return this.toTag()
    }
  }

  $.fn.mailing = function (url, log, statuses) {
    let self = this
    let pause = self.find('.status').text()
    let $log = $(log)
    let timeout = false
    let send = function (loop) {
      $.get(url)
        .done(function (data) {
          $log.prepend(data)
        })
        .always(function () {
          if (timeout !== false) {
            if (loop) {
              timeout = setTimeout(function () {
                send(loop)
              }, 200)
            } else {
              timeout = false
              self.find('.status').text(pause)
            }
          }
        })
    }
    self.find('a').click(function (event) {
      event.preventDefault()
      let $this = $(this)
      let action = $this.attr('href').replace(/^#/, '')
      self.find('.status').text(statuses[action])
      switch (action) {
        case 'pause':
          if (timeout) {
            clearTimeout(timeout)
            timeout = false
          }
          break
        case 'play':
          if (timeout) {
            clearTimeout(timeout)
          }
          timeout = setTimeout(function () {
            send(true)
          }, 200)
          break
        case 'step':
          if (timeout) {
            clearTimeout(timeout)
          }
          timeout = setTimeout(function () {
            send(false)
          }, 200)
          break
        case 'clear':
          $log.empty()
          break
      }
    })
  }

  $.fn.mailingDelivery = function (url, delivery, statuses, readonly) {
    let self = this
    let update = false
    let action = false
    let click = function (event) {
      event.preventDefault()
      if (action === false) {
        let $link = $(this)
        action = $.post(url, {
          delivery: delivery,
          statuses: statuses,
          readonly: readonly,
          action: $link.attr('href').replace(/^#/, '')
        }).done(function (data) {
          if (data && data.content) {
            self.html(data.content).find('a').click(click)
            if (data.alert) {
              alert(data.alert)
            }
          }
        }).always(function () {
          self.find('a').css({
            visibility: 'visible'
          })
          action = false
        })
        self.find('a').css({
          visibility: 'hidden'
        })
      }
    }
    setInterval(function () {
      if (update === false && action === false) {
        let jqXHR = $.post(url, {
          delivery: delivery,
          statuses: statuses,
          readonly: readonly,
          action: 'update'
        }).done(function (data) {
          if (jqXHR === update) {
            if (action === false && data && data.content) {
              self.html(data.content).find('a').click(click)
              if (data.alert) {
                alert(data.alert)
              }
            }
            update = false
          }
        })
        update = jqXHR
      }
    }, 60000)
    self.find('a').click(click)
  }

  $.fn.mailingCombinator = (function () {
    let cache = new Cache({
      alive: 3600000,
      localStorageKey: 'combinator.cache',
      filter: function (dump) {
        let result = {}
        let timeout = this.alive > 0
          ? Date.now() - this.alive
          : 0
        for (let key in dump) {
          if (dump.hasOwnProperty(key) && dump[key].time > timeout) {
            result[key] = dump[key]
          }
        }
        return result
      },
      read: function () {
        let dump = {}
        try {
          let item = localStorage.getItem(this.localStorageKey)
          dump = item === null ? {} : this.filter(JSON.parse(item))
        } catch (error) {
          console.log(error)
        }
        this.save(dump)
        return dump
      },
      save: function (dump) {
        try {
          localStorage.setItem(this.localStorageKey, JSON.stringify(this.filter(dump)))
        } catch (error) {
          console.log(error)
        }
      }
    })
    return function (url, tags, limit) {
      let self = this
      let loader = false
      let jqXHRs = {}
      let isLoad = function () {
        for (let key in jqXHRs) {
          if (jqXHRs.hasOwnProperty(key)) {
            return true
          }
        }
        return false
      }
      let isLoader = function (key, jqXHR) {
        return jqXHRs.hasOwnProperty(key) && (jqXHR === false ||jqXHRs[key] === jqXHR)
      }
      let showLoader = function (key, jqXHR) {
        jqXHRs[key] = jqXHR
        if (!loader) {
          loader = $('<i>', {
            'class': 'fa fa-refresh fa-spin',
            'aria-hidden': 'true',
            'style': 'position:relative;float:right;padding:5px'
          })
          self.data('ui-tagit').tagInput.closest('.ui-widget').append(loader)
        }
        return jqXHR
      }
      let hideLoader = function (key, jqXHR) {
        if (isLoader(key, jqXHR)) {
          delete jqXHRs[key]
        }
        if (loader && !isLoad()) {
          loader.remove()
          loader = false
        }
        return jqXHR
      }
      let $for = $(self.data('for'))
      let id = $for.attr('id')
      let $form = self
        .change(function () {
          let assignedTags = self.tagit('assignedTags')
          let request = {
            state: assignedTags.sort()
          }
          if (cache.has(request)) {
            hideLoader('change', false)
            $for.val(cache.get(request))
          } else {
            let jqXHR = showLoader('change', $.post(url, request).then(function (data, status) {
              if (status === 'success' && data && data.hasOwnProperty('combiner')) {
                cache.set(request, data.combiner)
                if (isLoader('change', jqXHR)) {
                  hideLoader('change', jqXHR)
                  $for.val(data.combiner)
                  $form.yiiActiveForm('updateAttribute', id, '')
                  $form.yiiActiveForm('validateAttribute', id)
                }
              }
            }, function () {
              hideLoader('change', jqXHR)
            }))
          }
        })
        .tagit({
          singleFieldDelimiter: ';',
          removeConfirmation: true,
          showAutocompleteOnFocus: true,
          beforeTagAdded: function (event, ui) {
            return tags.indexOf(ui.tagLabel) < 0 ? email.test(ui.tagLabel) : true
          },
          autocomplete: {
            source: function (request, response) {
              let assignedTags = self.tagit('assignedTags')
              let freeTags = tags.filter(function (tag) {
                return assignedTags.indexOf(tag) < 0
              })
              let value = self.data('ui-tagit').tagInput.val()
              let selectedTags = value.length === 0 ? [] : freeTags.filter(function (tag) {
                return tag.indexOf(value) >= 0
              })
              if (selectedTags.length === 0) {
                let request = {
                  query: value,
                  tags: assignedTags.sort(),
                  limit: limit
                }
                if (cache.has(request)) {
                  hideLoader('source', false)
                  response(cache.get(request).concat('').concat(freeTags))
                } else {
                  response(freeTags)
                  let jqXHR = showLoader('source', $.post(url, request)
                    .then(function (data, status) {
                      if (status === 'success' && Array.isArray(data) && data.length > 0) {
                        cache.set(request, data)
                        if (isLoader('source', jqXHR)) {
                          hideLoader('source', jqXHR)
                          response(data.concat('').concat(freeTags))
                        }
                      }
                    }, function () {
                      hideLoader('source', jqXHR)
                    }))
                }
              } else {
                response(selectedTags)
              }
            },
            delay: 400,
            minLength: 0
          }
        })
        .closest('form').on('beforeValidateAttribute', function (event, attribute, messages) {
          if (attribute.id === id && isLoader('change', false)) {
            messages.push('Данные еще не загружены')
            $form.yiiActiveForm('updateAttribute', id, messages)
          }
        })
    }
  })()

  $.fn.mailingCode = function (id, isTinymce) {
    let self = this
    let modal = $('#' + id)
    let $result = $('#' + id + '-result')
    let $help = $('#' + id + '-result ~ .help-block')
    let $options = $('#' + id + '-options')
    let $submit = $('#' + id + '-submit')
    let edit = function (code, callback) {
      $help.html(code.config.help ? code.config.help : '')
      let option = code.option.getInput(id)
      $('#' + id + '-option').closest('.form-group').replaceWith(option)
      if (code.config.option) {
        option.show()
      } else {
        option.hide()
      }
      let param = code.param.getInput(id)
      $('#' + id + '-param').closest('.form-group').replaceWith(param)
      if (code.config.param) {
        param.show()
      } else {
        param.hide()
      }
      $options.empty()
      code.options.each(function (key, value) {
        $options.append(value.getInput(id))
      })
      modal.change(function () {
        $result.val(code.toTag())
      })
      $submit.off('click').click(function () {
        modal.modal('hide')
        callback($result.val())
      })
      $result.val(code.toTag())
      modal.modal('show')
    }
    let preview = function (code) {
      let result = code.toTag()
      let preview = $(code.config.inline ? '<span>' : '<div>', {
        class: 'shordcode',
        'data-shordcode': result,
        contentEditable: false
      }).html(code.preview()).click(function () {
        code.fromTag(result)
        edit(code, function () {
          result = code.toTag()
          preview.attr({
            'data-shordcode': result
          }).html(code.preview())
        })
      })
      return preview
    }
    if (isTinymce) {
      tinymce.on('AddEditor', function (event) {
        let ed = event.editor
        if (ed.id === self.attr('id')) {
          ed.contentCSS.push('data:text/css;charset=utf-8;base64,LnNob3JkY29kZXtib3JkZXI6ZG90dGVkIDFweCAjNjY2fXNwYW4uc2hvcmRjb2Rle2Rpc3BsYXk6aW5saW5lLWJsb2NrfQ==')
          let menu = []
          $.fn.mailingCode.tags.each(function (tag, code) {
            if (code.config.css.length > 0) {
              ed.contentCSS.push(code.config.css)
            }
            menu.push({
              text: '[' + tag + '] - ' + code.config.comment,
              onclick: function () {
                let selection = ed.selection
                let newCode = code.create(selection.getContent({format: 'text'}))
                edit(newCode, function () {
                  if (newCode.config.inline) {
                    selection.setContent('<span class="replacer"></span>', {format: 'raw'})
                    $(selection.getNode()).find('.replacer').replaceWith(preview(newCode))
                  } else {
                    selection.setContent('', {format: 'raw'})
                    $(selection.getNode()).closest('body > *').after(preview(newCode))
                  }
                })
              }
            })
          })
          ed.addMenuItem('shordcode', {
            text: 'Шордкод',
            context: 'insert',
            menu: menu
          })
          //let loading = true;
          ed.on('BeforeSetContent', function (event) {
            //if (loading) {
              event.content = event.content.replace(shordcode, function (match) {
                return $('<span>').append($('<span>', {class: 'shordcode', 'data-shordcode': match}).text(match)).html()
              })
            //}
          })
          ed.on('SetContent', function () {
            //if (loading) {
              //loading = false
              $(ed.dom.doc).find('.shordcode').each(function () {
                let $this = $(this)
                let data = $this.data('shordcode')
                let result = exec(shordcode, data)
                if (!result) return;
                let code = $.fn.mailingCode.tags.has(result.groups.tag)
                  ? $.fn.mailingCode.tags.get(result.groups.tag)
                  : new $.fn.mailingCode.Code(result.groups.tag, {})
                let newCode = code.create('')
                newCode.fromTag(data)
                $this.replaceWith(preview(newCode))
              })
            //}
          })
          ed.on('GetContent', function (event) {
            let content = $('<span>').html(event.content)
            content.find('.shordcode').each(function () {
              let $this = $(this)
              $this.after($this.data('shordcode'))
              $this.remove()
            })
            event.content = content.html()
          })
        }
      })
    } else {
      let helpBlock = $('<div>', {
        class: 'shordcodes-block'
      }).text('Вы можете использовать следующие шордкоды:')
      $.fn.mailingCode.tags.each(function (tag, code) {
        helpBlock.append(' ').append($('<a>', {
          href: '#' + tag,
          title: code.config.comment
        }).click(function (event) {
          event.preventDefault()
          let start = self.prop('selectionStart')
          let end = self.prop('selectionEnd')
          let text = self.val()
          let newCode = code.create(text.substr(start, end))
          edit(newCode, function (result) {
            self.val(text.substr(0, start) + result + text.substr(end)).change()
          })
        }).text('[' + tag + ']'))
      })
      helpBlock.insertAfter(self)
    }
  }
  $.fn.mailingCode.Code = Code
  $.fn.mailingCode.Attribute = Attribute
  $.fn.mailingCode.codes = new Cache({})
  $.fn.mailingCode.tags = new Cache({})
  $.fn.mailingCode.create = function (config) {
    return function (tag) {
      return new $.fn.mailingCode.Code(tag, config)
    }
  }
  $.fn.mailingCode.init = function (tag, name) {
    let code = $.fn.mailingCode.codes.get(name)
    $.fn.mailingCode.tags.set(tag, code(tag))
  }

})(jQuery)