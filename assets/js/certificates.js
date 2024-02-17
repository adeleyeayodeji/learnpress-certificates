(function ($) {
  var $html,
    el_lp_data_config_cer,
    el_show_cer_popup_first,
    el_form_certificate_button,
    el_popup_cert,
    el_single_certificate,
    el_social_cert,
    el_need_upload_cert_img_to_server;

  var el_form_lp_cert_add_to_cart_woo;

  window.LP_Certificate = function (el, options) {
    var self = this,
      viewport = {
        width: 0,
        height: 0,
        templateWidth: 0,
        templateHeight: 0,
        ratio: 1
      },
      $el = $(el),
      $canvas = null,
      windowHeight,
      windowWith;

    var el_certificate_actions, el_download, el_certificate_result;

    function init() {
      windowHeight = $(window).height();
      windowWith = $(window).width();
      getElements();
      initCanvas();
      $(document).on(
        "click",
        '[data-cert="' + $el.attr("id") + '"]',
        function (e) {
          e.preventDefault();
          download();
        }
      );
      self.$canvas = $canvas;
    }

    function getElements() {
      el_certificate_actions = $(".certificate-actions");
    }

    function initCanvas() {
      if (!$canvas) {
        $canvas = $el.find("canvas");
        $canvas = new fabric.Canvas($canvas.get(0));
        $canvas.selection = false;

        $.each(options.layers, function (i) {
          total_layer++;
        });

        console.log(options.layers);

        $.each(options.layers, function (i, layer) {
          if (!layer.type) return;

          if ($.isPlainObject(layer)) {
            createLayer(layer);
          }
        });
      }
    }

    function dataURItoBlob(dataURI) {
      // convert base64 to raw binary data held in a string
      // doesn't handle URLEncoded DataURIs - see SO answer #6850276 for code that does this
      var byteString = atob(dataURI.split(",")[1]);

      // separate out the mime component
      var mimeString = dataURI.split(",")[0].split(":")[1].split(";")[0];

      // write the bytes of the string to an ArrayBuffer
      var ab = new ArrayBuffer(byteString.length);

      // create a view into the buffer
      var ia = new Uint8Array(ab);

      // set the bytes of the buffer to the correct values
      for (var i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
      }

      // write the ArrayBuffer to a blob, and you're done
      var blob = new Blob([ab], { type: mimeString });
      return blob;
    }

    var name_file_download = "certificate";

    function download() {
      if (el_download.length) {
        var downloadType = el_download.data("type-download");

        if (undefined !== options.name) {
          name_file_download = options.name;
        }

        switch (downloadType) {
          case "pdf":
            downloadPDF();
            break;
          case "image":
          default:
            downloadImage();
            break;
        }
      }
    }

    function downloadImage() {
      var args = {
        format: "png",
        multiplier: 1 / $canvas.getZoom()
      };

      var data_url = $canvas.toDataURL();
      var imageBlob = dataURItoBlob(data_url);
      var ajaxData = new FormData();

      name_file_download = name_file_download + ".png";

      ajaxData.append("files", imageBlob, name_file_download);

      downloadjs($canvas.toDataURL(args), name_file_download);

      return false;
    }

    function createPreview() {
      var args = {
        format: "png",
        multiplier: 1 / $canvas.getZoom(),
        quality: 1
      };

      var data = $canvas.toDataURL(args);
      var $img = $('<img class="certificate-result" />').insertBefore($el);
      el_certificate_result = $(".certificate-result");

      $img.attr("src", data);

      // Resize image certificate preview
      setTimeout(function () {
        if ($img.width() > windowWith) {
          el_certificate_result.css("width", "100%");
        }
      }, 100);
    }

    var total_layer = 0;
    var total_layer_loaded = 0;

    function check_layers_added_done() {
      total_layer_loaded++;

      if (total_layer_loaded === total_layer) {
        var url_img_cer_bg = options.template;
        //var url_img_cer_bg = 'https://cdn.glitch.com/4c9ebeb9-8b9a-4adc-ad0a-238d9ae00bb5%2Fmdn_logo-only_color.svg?1535749917189';

        var img_cer_bg = new Image();
        var args_fabric = {};

        // Check image crossOrigin
        if (undefined !== localize_lp_cer_js) {
          var is_same_domain = new RegExp("^" + localize_lp_cer_js.base_url);

          if (!is_same_domain.test(url_img_cer_bg)) {
            img_cer_bg.crossOrigin = "Anonymous";
            args_fabric.crossOrigin = "Anonymous";
          }
        }

        img_cer_bg.onload = function () {
          viewport = {
            width: this.width,
            height: this.height
          };

          fabric.Image.fromURL(
            img_cer_bg.src,
            function (img) {
              $canvas.backgroundImage = img;
              updateView();
              createPreview();

              if (el_need_upload_cert_img_to_server.length) {
                saveImageToServer();
              }

              if (el_certificate_actions.length) {
                el_download = el_certificate_actions.find(".download");
              }

              // Show popup certificate
              $(document).triggerHandler("learn-press/certificates/loaded");
            },
            args_fabric
          );
        };

        img_cer_bg.src = url_img_cer_bg;
      }
    }

    function saveImageToServer() {
      var data = {
        action: "lpCertCreateImage",
        data64: el_certificate_result.attr("src"),
        name_image: options.key_cer
      };

      $.ajax({
        url: localize_lp_cer_js.url_ajax,
        data: data,
        method: "post",
        dataType: "json",
        beforeSend: function () {
          el_certificate_actions.append(
            '<li class="fa fa-spinner">Loading share social...</li>'
          );
        },
        success: function (rs) {
          if (rs.code == 1) {
            $.each(el_social_cert, function (e) {
              var url_cert_share = $(this).attr("href") + rs.url_cert;

              $(this).attr("href", url_cert_share);
            });

            el_social_cert.show();
          }
        },
        complete: function () {
          el_certificate_actions.find(".fa-spinner").remove();
        },
        error: function (e) {
          console.log(e);
        }
      });
    }

    function htmlDecode(input) {
      var e = document.createElement("div");
      e.innerHTML = input;
      return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
    }

    function createLayer(args) {
      args.text = htmlDecode(args.text) || "";

      var defaults = $.extend(
          {
            fontSize: 24,
            left: 0,
            top: 0,
            lineHeight: 1,
            originX: "center",
            originY: "center",
            fontFamily: "Helvetica",
            fieldType: "custom",
            qr_size: 40
          },
          args
        ),
        text = args.text || "";

      try {
        var $object = null;
        var is_url = /^(https?|s?ftp):\/\//i.test(args.text);

        if (args.fieldType == "verified-link" && is_url) {
          var qr_code = new Image();
          defaults.type = "image";
          defaults.height = defaults.qr_size;
          defaults.width = defaults.qr_size;
          qr_code.crossOrigin = "Anonymous";

          qr_code.onload = function () {
            $object = new fabric.Image(qr_code, defaults);
            $canvas.add($object);

            check_layers_added_done();
          };

          qr_code.src = args.text;
        } else {
          $object = new fabric.Text("", defaults);

          //console.log(defaults);

          $canvas.add($object);

          check_layers_added_done();
        }
      } catch (e) {
        console.log(e);
      }

      return $object;
    }

    function getMaxWidth() {
      return $el.width();
    }

    function updateView() {
      $canvas.setHeight(viewport.height);
      $canvas.setWidth(viewport.width);
      $canvas.setZoom(viewport.ratio);
      $canvas.calcOffset();
      $canvas.renderAll();

      //fitImage();
    }

    function fitImage() {
      var $preview = $el.siblings(".certificate-result"),
        scrWidth = $el.parent().width(),
        scrHeight =
          $(window).height() - (60 + parseInt($el.parent().position().top)),
        maxWidth = viewport.width,
        maxHeight = viewport.height;

      var scale = Math.min(scrWidth / maxWidth, scrHeight / maxHeight);

      maxWidth = maxWidth * scale;
      if (maxWidth) {
        $preview.css({
          //maxWidth: maxWidth * scale
        });
      }
    }

    function setLayerProp($layer, prop, value) {
      var options = {};
      switch (prop) {
        case "textAlign":
          //$layer.originX = value;
          break;
        case "color":
          //$layer.set('fill', value);
          options["fill"] = value;
          break;
        case "scaleX":
        case "scaleY":
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
          options[prop] = Math.abs(value);
          break;
        case "top":
        case "left":
        case "angle":
          options[prop] = parseInt(value);
          break;
        default:
          options[prop] = value;
      }
      $.each(options, function (k, v) {
        $layer.set(k, v);
      });
      $layer.setCoords();
    }

    function downloadPDF() {
      var getImageFromUrl = function (url, callback) {
        var img = new Image();
        // img.crossOrigin = 'anonymous'

        img.onError = function () {
          alert('Cannot load image: "' + url + '"');
        };
        img.onload = function () {
          callback(img, img.width, img.height);
        };
        img.src = url;
      };

      var createPDF = function (imgData, width, height) {
        var doc, pdfWidth, pdfHeight;

        if (width >= height) {
          doc = new jsPDF("l", "mm", [width, height]);
          pdfWidth = doc.internal.pageSize.getWidth();
          pdfHeight = (height * pdfWidth) / width;
        } else {
          doc = new jsPDF("p", "mm", [width, height]);
          pdfWidth = doc.internal.pageSize.getWidth();
          // pdfHeight = (height * pdfWidth) / width;
          pdfHeight = doc.internal.pageSize.getHeight();
        }

        doc.addImage(imgData, "png", 0, 0, pdfWidth, pdfHeight);
        doc.save(name_file_download + ".pdf");
      };

      el_certificate_result = $(".certificate-result");

      if (el_certificate_result.length) {
        var url = el_certificate_result.attr("src");

        getImageFromUrl(url, createPDF);
      }
    }

    init();
  };

  function getElements() {
    $html = $("html, body");
    el_lp_data_config_cer = $(".lp-data-config-cer");
    el_show_cer_popup_first = $("input[name=f_auto_show_cer_popup_first]");
    el_form_certificate_button = $('form[name="certificate-form-button"]');
    el_popup_cert = $("#certificate-popup");
    el_single_certificate = $(".single-certificate-content");
    el_social_cert = $(".share-social-cert");
    el_need_upload_cert_img_to_server = $(
      "input[name=need_upload_cert_img_to_server]"
    );

    el_form_lp_cert_add_to_cart_woo = $(
      "form[name=form-lp-cert-add-to-cart-woo]"
    );
  }

  function popupCer() {
    if (el_popup_cert.length) {
      function close() {
        el_popup_cert.fadeOut(function () {
          $html.css("overflow", "auto");
        });
      }

      function open() {
        $html.css("overflow", "hidden");
        el_popup_cert.fadeIn();
      }

      $(document).on("learn-press/certificates/loaded", function () {
        el_popup_cert.addClass("ready").hide();

        $html
          .off("keyup")
          .on("keyup", function (e) {
            if (e.keyCode === 27) {
              close();
            }
          })
          .off("click")
          .on("click", ".close-popup", function (e) {
            close();
            e.preventDefault();
          });

        el_form_certificate_button.on("submit", function (e) {
          e.preventDefault();
          open();
        });

        if (el_show_cer_popup_first.length) {
          open();
        }
      });
    }
  }

  function addCertToCartWoo() {
    el_form_lp_cert_add_to_cart_woo.on("submit", function (e) {
      e.preventDefault();
      $el_form_lp_cert_add_to_cart_woo = $(this);
      var el_btn_add_cert_to_cart_woo = $(this).find(
        ".btn-add-cert-to-cart-woo"
      );

      var data = $(this).serialize();

      data += "&action=lp_cert_add_to_cart_woo";

      $.ajax({
        url: localize_lp_cer_js.url_ajax,
        data: data,
        method: "post",
        beforeSend: function () {
          el_btn_add_cert_to_cart_woo.append(
            '<span class="fa fa-spinner"></span>'
          );
        },
        success: function (rs) {
          if (rs.code === 1) {
            if (undefined != rs.redirect_to) {
              window.location.replace(rs.redirect_to);
            } else {
              $el_form_lp_cert_add_to_cart_woo
                .closest(".wrapper-lp-cert-add-to-cart-woo")
                .append(rs.button_view_cart);
              $el_form_lp_cert_add_to_cart_woo.remove();
            }
          } else {
            alert(rs.message);
          }
        },
        error: function (e) {
          console.log(e);
        },
        complete: function () {
          el_btn_add_cert_to_cart_woo.find("span").remove();
        }
      });
    });
  }

  $(document).ready(function () {
    getElements();
    el_social_cert.hide();

    if (!el_show_cer_popup_first.length) {
      el_form_certificate_button.css("display", "inline-block");
    }

    //console.log(localize_lp_cer_js);

    /*** Create certificates ***/
    if (el_lp_data_config_cer.length) {
      try {
        $.each(el_lp_data_config_cer, function (i) {
          var data_config_cer = JSON.parse($(this).val()) || {};
          $(this).val("");
          var id_div_parent = "#" + $(this).closest("div").attr("id");

          var cer = LP_Certificate(id_div_parent, data_config_cer);

          //console.log(data_config_cer);
        });
      } catch (e) {
        console.log(e);
      }
    }

    // Popup Certificate
    popupCer();

    if (el_form_lp_cert_add_to_cart_woo.length) {
      addCertToCartWoo();
    }
  });
})(jQuery);
