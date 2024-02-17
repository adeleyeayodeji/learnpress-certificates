/**
 * Plugin LearnPress Certificates.
 *
 * @author ThimPress
 * @package Certificates/JS
 * @version 3.0.0
 */
(function ($) {
  var mediaSelector = {
    __onSelect: null,
    __multiple: false,
    __context: null,
    activeFrame: false,
    frame: function (args) {
      args = $.extend({}, args || {});
      var k = Math.random() * 100;
      if (!this._frame) {
        this._frame = [];
      }
      if (!this._frame[k]) {
        this._frame[k] = wp.media({
          title: args.title || "Select Media",
          button: {
            text: args.button_text || "Insert"
          },
          multiple: args.multiple
        });
        this._frame[k].state("library").on("select", this.select);
      }
      return this._frame[k];
    },

    select: function () {
      if ($.isFunction(mediaSelector.__onSelect)) {
        var source = this.get("selection");

        if (!mediaSelector.__multiple) {
          source = source.single().toJSON();
        } else {
          source = source.toJSON();
        }
        mediaSelector.__onSelect.call(
          mediaSelector._frame,
          source,
          mediaSelector.__context
        );
        mediaSelector.__onSelect = null;
        mediaSelector.__context = null;
      }
    },
    open: function (args) {
      args = $.extend(
        {
          multiple: false,
          context: null,
          onSelect: function () {}
        },
        args || {}
      );
      if ($.isFunction(args.onSelect)) {
        mediaSelector.__onSelect = args.onSelect;
        mediaSelector.__multiple = args.multiple;
        mediaSelector.__context = args.context;
        var f = mediaSelector.frame(args);
        f.open();
      }
    }
  };

  $(document).ready(function () {
    var _ = {
      now:
        Date.now ||
        function () {
          return new Date().getTime();
        },
      debounce: function (func, wait, immediate) {
        var timeout, args, context, timestamp, result;

        var later = function () {
          var last = _.now() - timestamp;

          if (last < wait && last >= 0) {
            timeout = setTimeout(later, wait - last);
          } else {
            timeout = null;
            if (!immediate) {
              result = func.apply(context, args);
              if (!timeout) context = args = null;
            }
          }
        };

        return function () {
          context = this;
          args = arguments;
          timestamp = _.now();
          var callNow = immediate && !timeout;
          if (!timeout) timeout = setTimeout(later, wait);
          if (callNow) {
            result = func.apply(context, args);
            context = args = null;
          }
          return result;
        };
      }
    };

    // Global

    function ajaxUpdateCourseCertificate(id, cert) {
      $.ajax({
        url: "",
        data: {
          "lp-ajax": "update-course-certificate",
          course_id: id,
          cert_id: cert
        },
        success: function () {
          $("#certificate-browser")
            .find(".theme.updating")
            .removeClass("updating");
        }
      });
    }

    $(document)
      .on("click", ".button-assign-certificate", function (e) {
        e.preventDefault();
        var $wrapper = $("#certificate-browser").find(".themes"),
          $themes = $wrapper.find(".theme"),
          $selected = $(this).closest(".theme");
        $themes.removeClass("active");
        $wrapper.prepend($selected.addClass("active updating"));

        // Update
        ajaxUpdateCourseCertificate($("#post_ID").val(), $selected.data("id"));
      })
      .on("click", ".button-remove-certificate", function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).closest(".theme").removeClass("active").addClass("updating");

        // Update
        ajaxUpdateCourseCertificate($("#post_ID").val(), 0);
      });

    if (typeof lpCertificatesSettings === "undefined") {
      console.log("lpCertificatesSettings is not defined!");
      return;
    }

    window.$LP_Certificates = new Vue({
      el: "#learn-press-certificates",
      template: "#tmpl-certificates",
      data: $.extend(
        {
          dragover: false,
          viewport: {
            width: 0,
            height: 0,
            templateWidth: 0,
            templateHeight: 0,
            ratio: 1
          },
          $view: false,
          $layerOptions: null,
          loaded: false,
          i18n: {},
          template: "xxx"
        },
        lpCertificatesSettings
      ),

      computed: {
        getMaxWidth: function () {
          return Math.random();
        }
      },
      created: function () {
        var that = this;
        setTimeout(function () {
          that.init();
        }, 300);
      },

      methods: {
        init: function () {
          var that = this;
          this.$img = this.$(".cert-template");
          this.$img_preload = document.getElementById("cert-template-preload");
          this.initCanvas();
          this.initDragAndDrop();
          if ($("#cert-template-preload").length) {
            this.viewport = {
              width: this.$img_preload.width,
              height: this.$img_preload.height,
              templateWidth: this.$img_preload.naturalWidth,
              templateHeight: this.$img_preload.naturalHeight,
              ratio: this.$img_preload.width / this.$img_preload.naturalWidth
            };
            this.updateView();
          }

          this.$img
            .on("load", function () {
              that.viewport = {
                width: this.width,
                height: this.height,
                templateWidth: this.naturalWidth,
                templateHeight: this.naturalHeight,
                ratio: this.width / this.naturalWidth
              };
              console.log(that.viewport);
              that.loaded && that.ajaxUpdateTemplate();
              that.updateView();
            })
            .trigger("load");

          this.$layerOptions = $(".cert-layer-options");

          $(document).on(
            "certificate/change-layer-option",
            _.debounce(function (e, data) {
              var options = {};
              options[data.name] = data.value;
              that.updateActiveLayer(options);
            }, 100)
          );

          $(window).on("resize.certificate-update-view", function () {
            var timer = $(this).data("timer");
            timer && clearTimeout(timer);
            timer = setTimeout(function () {
              that.updateView();
            }, 300);
          });

          $(document).on("click", ".layer-center", function () {
            var type = $(this).data("center");
            switch (type) {
              case "center-h":
                that.centerH();
                break;
              case "center-v":
                that.centerV();
                break;
              case "center":
                that.center();
                break;
            }
          });

          $(document).on("click", ".cert-layers .layer", function (e) {
            var $layer = that.findLayer($(this).data("layer"));
            $layer && that.$canvas.setActiveObject($layer);
            if ($(e.target).is("a")) {
              that.deleteLayer();
              e.preventDefault();
            }
          });

          this.loaded = true;
        },
        findLayer: function (name) {
          var $layers = this.$canvas.getObjects();
          for (var i in $layers) {
            if ($layers[i].name === name) {
              return $layers[i];
            }
          }
          return false;
        },
        addCustomPreview: function () {},
        centerV: function ($layer) {
          if (!$layer) {
            $layer = this.getActiveLayer();
          }

          if (!$layer) {
            return;
          }

          var position = {
            top:
              (this.$canvas.height / this.viewport.ratio -
                $layer.height * Math.abs($layer.scaleY)) /
              2
          };

          if ($layer.originY === "center") {
            position.top += $layer.height / 2;
          }

          $layer.set(position);
          $layer.setCoords();
          this.updateView();
          this.updateActiveLayer(position);
        },
        centerH: function ($layer) {
          if (!$layer) {
            $layer = this.getActiveLayer();
          }

          if (!$layer) {
            return;
          }

          var position = {
            left:
              (this.$canvas.width / this.viewport.ratio -
                $layer.width * Math.abs($layer.scaleX)) /
              2
          };

          if ($layer.originX === "center") {
            position.left += $layer.width / 2;
          }

          $layer.set(position);
          $layer.setCoords();
          this.updateView();
          this.updateActiveLayer(position);
        },
        center: function ($layer) {
          this.centerV($layer);
          this.centerH($layer);
        },
        deleteLayer: function () {
          if (!confirm(this.i18n.confirm_remove_layer)) {
            return;
          }
          var $layer = this.getActiveLayer(),
            name = $layer.get("name");
          if ($layer) {
            this.$canvas.remove($layer);
            Vue.http
              .post(
                "",
                {
                  layer: name
                },
                {
                  emulateJSON: true,
                  params: {
                    "lp-ajax": "cert-remove-layer",
                    id: this.id
                  }
                }
              )
              .then(function () {
                $(".cert-layers")
                  .children()
                  .filter('[data-layer="' + name + '"]')
                  .remove();
              });
          }
        },
        getActiveLayer: function () {
          return this.$canvas.getActiveObject();
        },
        getMaxWidth: function () {
          var w = this.$().width();
          return w - 60;
        },
        updateActiveLayer: function (options, $layer) {
          $layer = $layer ? $layer : this.getActiveLayer();
          if (!$layer) {
            return;
          }
          for (var prop in options) {
            this.setLayerProp($layer, prop, options[prop]);
          }
          this.$canvas.renderAll();
          this.ajaxUpdateLayer($layer);
        },
        calculate: function () {
          this.viewport = $.extend(this.viewport, {
            width: this.$img.width(),
            height: this.$img.height(),
            ratio: this.$img.width() / this.viewport.templateWidth
          });
        },
        updateView: function () {
          var maxWidth = this.getMaxWidth();
          this.calculate();
          if (this.viewport.templateWidth < maxWidth && this.template) {
            this.$("#cert-design-view")
              .css("width", this.viewport.width + 60)
              .addClass("small");
          } else {
            this.$("#cert-design-view").css("width", "").removeClass("small");
          }
          this.$canvas.setHeight(this.viewport.height);
          this.$canvas.setWidth(this.viewport.width);
          this.$canvas.setZoom(this.viewport.ratio);
          this.$canvas.calcOffset();
          this.$canvas.renderAll();
        },
        $: function (selector) {
          var $el = $(this.$el);
          return selector ? $el.find(selector) : $el;
        },
        getLayerOptions: function ($layer) {
          return $layer ? $layer.toObject(this.getExtendedFields()) : {};
        },
        getExtendedFields: function () {
          var _fields = [
              "name",
              "fieldType",
              "display",
              "customText",
              "format",
              "variable",
              "formatDate",
              "qr_size",
              "fontFile"
            ],
            fields = $(document).triggerHandler(
              "learn_press_certificates_extended_fields",
              [_fields]
            );
          if (typeof fields === "undefined") {
            fields = _fields;
          }
          return fields;
        },
        showControls: function (e) {},
        showLines: function () {
          var $el = this.getActiveLayer();
          if (!$el) {
            this.hideLines();
            return;
          }
          this.$("#cert-design-view").addClass("dragover");
          this.$("#cert-design-line-horizontal").css(
            "top",
            $el.top * this.viewport.ratio
          );
          this.$("#cert-design-line-vertical").css(
            "left",
            $el.left * this.viewport.ratio - 1
          );
        },
        hideLines: function () {
          this.$("#cert-design-view").removeClass("dragover");
        },
        toFixedX: function (num) {
          return Math.ceil(num * 10) / 10;
        },
        hideControls: function () {},

        getLayers: function () {
          var layers = {},
            $objects = this.$canvas.getObjects();

          for (var i in $objects) {
            layers[$objects[i].name] = this.getLayerOptions($objects[i]);
          }

          return layers;
        },

        ajaxUpdateLayers: _.debounce(function () {
          var that = this;

          return Vue.http.post(
            "",
            {
              layers: this.getLayers()
            },
            {
              emulateJSON: true,
              params: {
                "lp-ajax": "cert-update-layers",
                id: this.id
              }
            }
          );
        }, 300),

        ajaxUpdateLayer: _.debounce(function (layer, loadSettings) {
          layer = $.isPlainObject(layer) ? layer : this.getLayerOptions(layer);

          var that = this,
            hash = JSON.stringify(layer).md5();
          if (hash === layer.hash) {
            return;
          }

          return Vue.http
            .post(
              "",
              {
                layer: layer,
                "load-settings": loadSettings ? "yes" : "no"
              },
              {
                emulateJSON: true,
                params: {
                  "lp-ajax": "cert-update-layer",
                  id: this.id
                }
              }
            )
            .then(function (response) {
              var $layerOptions = $(response.body);
              if ($layerOptions.hasClass("cert-layer-options")) {
                that.$layerOptions = $layerOptions;
                $("#certificates-options")
                  .removeClass("loading")
                  .find(".inside")
                  .html(that.$layerOptions);
              }
            });
        }, 300),

        ajaxUpdateTemplate: _.debounce(function () {
          return Vue.http.post(
            "",
            {
              template: this.template
            },
            {
              emulateJSON: true,
              params: {
                "lp-ajax": "cert-update-template",
                id: this.id
              }
            }
          );
        }, 300),

        ajaxLoadLayer: _.debounce(function ($layer) {
          var that = this;
          $("#certificates-options").show().addClass("loading");
          return Vue.http
            .post(
              "",
              {
                layer: $layer.get("name")
              },
              {
                emulateJSON: true,
                params: {
                  "lp-ajax": "cert-load-layer",
                  id: this.id
                }
              }
            )
            .then(function (response) {
              that.$layerOptions = $(response.body);
              $("#certificates-options")
                .removeClass("loading")
                .find(".inside")
                .html(that.$layerOptions);
            });
        }, 300),

        addLayerList: function ($layer) {
          var $l = $(
            '<div class="layer" data-layer="' +
              $layer.name +
              '">' +
              "Layer #" +
              $layer.text +
              '<a href=""></a></div>'
          );
          $(".cert-layers").prepend($l);
        },

        addLayer: function ($layer, args) {
          args = $.extend(
            {
              setActive: true
            },
            args || {}
          );

          if ($.isPlainObject($layer)) {
            $layer = this.createLayer($layer);
          }
          if ($layer) {
            this.loaded && this.ajaxUpdateLayer($layer, true);
            this.$canvas.add($layer);
            this.addLayerList($layer);
            if (args.setActive) {
              this.$canvas.setActiveObject($layer);
            }
            this.$canvas.renderAll();
          }
          return $layer;
        },

        createLayer: function (args) {
          try {
            var defaults = $.extend(
                {
                  fontSize: 24,
                  left: 0,
                  top: 0,
                  lineHeight: 1,
                  originX: "center",
                  originY: "center",
                  fontFamily: "Helvetica",
                  name: this.uniqueId(),
                  fieldType: "custom",
                  variable: ""
                },
                args
              ),
              text = args.text || "",
              $object = new fabric.Text(text, defaults),
              that = this;

            if (args.fieldType == "verified-link") {
              defaults.qr_size = 40;
            }

            var is_url = /^(https?|s?ftp):\/\//i.test(args.text);
            if (
              args.fieldType == "verified-link" &&
              args.qr_size > 0 &&
              is_url
            ) {
              var qr_code = new Image();
              defaults.type = "image";
              defaults.height = defaults.qr_size;
              defaults.width = defaults.qr_size;
              qr_code.crossOrigin = "Anonymous";
              qr_code.src = args.text;
              $object = new fabric.Image(qr_code, defaults);
            }
            $object.set({
              borderColor: "#AAA",
              cornerColor: "#666",
              cornerSize: 7,
              transparentCorners: true,
              padding: 0
            });
            $.each(defaults, function (k, v) {
              that.setLayerProp($object, k, v);
            });

            var $_object = $(document).triggerHandler(
              "learn_press_certificate_layer_obj",
              [$object, args]
            );
            if (typeof $_object === "object") {
              $object = $_object;
            }
          } catch (e) {
            console.log(e);
          }
          return $object;
        },

        // Update options of a layer to settings panel
        updateLayerOptions: _.debounce(function ($layer) {
          var that = this,
            options = this.getLayerOptions(
              $layer ? $layer : this.getActiveLayer()
            ),
            $opt = this.$layerOptions.find(":input");

          $.each(options, function (k, v) {
            switch (k) {
              case "fontFamily":
                break;
              case "fontStyle":
              case "fontWeight":
                if (v === "normal") {
                  v = "";
                }
                break;
              case "originY":
                if (v === "middle") {
                  v = "center";
                }
                break;
              case "top":
              case "left":
                v = parseInt(v);

                break;
              case "angle":
              case "scaleX":
              case "scaleY":
                v = that.toFixedX(v);
            }
            $opt.filter('[name="' + k + '"]').val(v);
          });

          $("#certificates-options").show();
        }, 300),

        // Set property of a layer
        setLayerProp: function ($layer, prop, value) {
          var options = {};
          if (value === undefined) {
            value = "";
          }
          switch (prop) {
            case "color":
              options["fill"] = value;
              break;
            case "scaleX":
            case "scaleY":
              if (isNaN(value)) {
                value = 0;
              }
              if (value < 0) {
                if (prop === "scaleX") {
                  $layer.flipX = true;
                } else {
                  $layer.flipY = true;
                }
              } else {
                if (prop === "scaleX") {
                  $layer.flipX = false;
                } else {
                  $layer.flipY = false;
                }
              }
              options[prop] = this.toFixedX(Math.abs(value));
              break;
            case "top":
            case "left":
              if (isNaN(value)) {
                value = 0;
              }
              options[prop] = parseInt(value);
              break;
            case "angle":
              options[prop] = this.toFixedX(value);
              break;
            case "fontFamily":
              value = "" + value;
              $layer.set("fontFamily", value.replace(/\+/, " "));
              break;
            default:
              options[prop] = value;
          }

          if (options.flipX) {
            options.scaleX = -this.toFixedX(options.scaleX);
          }
          if (options.flipY) {
            options.scaleY = -this.toFixedX(options.scaleY);
          }

          $.each(options, function (k, v) {
            $layer.set(k, v);
          });
          $layer.setCoords();
        },
        onObjectSelected: function (e) {
          //this.$canvas.bringToFront(e.target);
          var layerOptions = this.getLayerOptions(e.target);
          for (var i in layerOptions) {
            this.setLayerProp(e.target, i, layerOptions[i]);
          }
          $(".cert-layers")
            .children()
            .removeClass("active")
            .filter('[data-layer="' + e.target.name + '"]')
            .addClass("active");
          this.ajaxLoadLayer(e.target);
        },
        onObjectMoving: function (event) {
          this.updateLayerOptions();
          this.ajaxUpdateLayer(event.target);
          this.showLines();
        },
        onObjectRotating: function (event) {
          this.updateLayerOptions();
          this.ajaxUpdateLayer(event.target);
          var $object = this.$canvas.getActiveObject(),
            angle = this.toFixedX($object.angle);
          this.setPropSlider("angle", angle);
          this.hideLines();
        },
        onObjectMouseup: function () {
          this.hideLines();
        },
        onObjectMousedown: function () {
          this.showLines();
        },
        onBeforeSelectionCleared: function (e) {
          $("#certificates-options").hide();
          $(".cert-layers").children().removeClass("active");
          this.hideLines();
        },
        onObjectModified: function (e) {
          this.showControls(e);
        },
        limitObjectScale: function () {
          var $object = this.$canvas.getActiveObject(),
            scaleX = this.toFixedX($object.scaleX),
            scaleY = this.toFixedX($object.scaleY);
          if ($object.flipX) {
            scaleX = -scaleX;
          }
          if ($object.flipY) {
            scaleY = -scaleY;
          }
          this.ajaxUpdateLayer($object);
          this.setPropSlider("scaleX", scaleX);
          this.setPropSlider("scaleY", scaleY);
        },
        setPropSlider: function (name, value) {
          this.$layerOptions
            .find('input[name="' + name + '"]')
            .val(value)
            .siblings(".cert-option-slider")
            .slider("option", "value", value);
        },
        initCanvas: function () {
          if (!this.$canvas) {
            var that = this,
              $canvas = this.$("canvas");

            this.$canvas = new fabric.Canvas($canvas.get(0), this.layers);
            this.$canvas
              .on({
                "object:selected": this.onObjectSelected,
                "object:moving": this.onObjectMoving,
                "object:rotating": this.onObjectRotating,
                "mouse:up": this.onObjectMouseup,
                "mouse:down": this.onObjectMousedown,
                "before:selection:cleared": this.onBeforeSelectionCleared,
                "object:modified": this.onObjectModified
              })
              .observe("object:scaling", this.limitObjectScale);
            this.$canvas.selection = false;
            $.each(this.layers, function (i, layer) {
              if (!layer.type) return;
              that.addLayer(layer, { setActive: false });
            });

            _.debounce(this.updateView, 300)();
            console.log("initCanvas");
          }
        },
        initDragAndDrop: function () {
          var that = this;
          this.$view = $(this.$el).find("#cert-design-view");

          $(".cert-fields li").draggable({
            helper: "clone",
            drag: function (e, ui) {
              var targetOffset = that.$view.offset();
              that.dragover = {
                left: ui.offset.left - targetOffset.left + 26,
                top: ui.offset.top - targetOffset.top + 13
              };

              if (that.dragover.left < 0 || that.dragover.top < 0) {
                that.dragover = false;
                return;
              }

              that.updateLines();
            }
          });
          $("#cert-design-editor").droppable({
            over: function (e, ui) {
              that.dragover = true;
              that.dragover = ui.position;
              that.updateLines();
            },
            out: function () {
              that.dragover = false;
            },
            drop: function (e, ui) {
              that.dragover = false;
              var parentOffset = that.$view.find(".canvas-container").offset();
              var left =
                  (ui.offset.left -
                    parentOffset.left +
                    ui.draggable.outerWidth() / 2) /
                  that.viewport.ratio,
                top =
                  (ui.offset.top -
                    parentOffset.top +
                    ui.draggable.outerHeight() / 2) /
                  that.viewport.ratio;
              that.addLayerByDrop({
                top: top,
                left: left,
                text: ui.draggable.text().trim(),
                fieldType: ui.draggable.data("type")
              });
            }
          });

          $(".cert-layers").sortable({
            axis: "y",
            start: function (e, ui) {
              var $children = $(this).children();
              ui.item.data("position", $children.index(ui.item));
            },
            update: function (e, ui) {
              var $obj = that.$canvas.getActiveObject();

              if (!$obj) {
                return;
              }

              var $children = $(this).children(),
                oldPosition = ui.item.data("position"),
                newPosition = $children.index(ui.item),
                i = 0;

              if (newPosition > oldPosition) {
                for (i = 0; i < newPosition - oldPosition; i++) {
                  $obj.sendBackwards();
                }
              } else {
                for (i = 0; i < oldPosition - newPosition; i++) {
                  $obj.bringForward();
                }
              }

              that.ajaxUpdateLayers();
            }
          });
        },

        updateRulers: function () {
          if (this.dragover) {
          }
        },
        updateLines: function () {
          if (this.dragover) {
            $(this.$el)
              .find(".cert-design-line")
              .filter(".horizontal")
              .css("top", this.dragover.top)
              .end()
              .filter(".vertical")
              .css("left", this.dragover.left);
          }
        },
        uniqueId: function () {
          function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
              .toString(16)
              .substring(1);
          }

          return s4() + s4() + s4() + s4();
        },
        selectTemplate: function () {
          mediaSelector.open({
            context: this,
            onSelect: function (source, that) {
              if (source.sizes) {
                that.template = source.sizes.full.url;
              } else {
                that.template = source.url;
              }

              that.updateView();
            }
          });
        },
        setPreview: function () {
          alert(0);
        },
        addLayerByDrop: function (args) {
          var $layer = this.createLayer(args);

          if ($layer) {
            this.addLayer($layer);
            this.$canvas.calcOffset();
            this.$canvas.renderAll();
          }
          return $layer;
        }
      }
    });
  });
})(jQuery);
