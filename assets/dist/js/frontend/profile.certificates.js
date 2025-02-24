/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!********************************************************!*\
  !*** ./assets/src/js/frontend/profile.certificates.js ***!
  \********************************************************/
const {
  addQueryArgs
} = wp.url;
const showListCertificates = () => {
  const elementCertificateProfile = document.querySelector('.learnpress-certificates-profile');
  if (!elementCertificateProfile) {
    return;
  }
  const userID = elementCertificateProfile.querySelector('input[name="userID"]').value;
  let filter = {
    userID: userID,
    page: 1
  };

  //loader template tab
  const skeletonTab = () => {
    getResponse(elementCertificateProfile, {
      ...filter
    });
  };
  const getResponse = async (ele, filter) => {
    const skeleton = ele.querySelector('.lp-skeleton-animation');
    try {
      const response = await wp.apiFetch({
        path: addQueryArgs('lp/v1/certificate/items-profile', filter),
        method: 'GET'
      });
      const {
        data,
        status,
        message
      } = response;
      if (status === 'error') {
        throw new Error(message || 'Error');
      }
      data && ele.insertAdjacentHTML('beforeend', data.template);
      getImageCertificate(data.certKey);
    } catch (error) {
      ele.insertAdjacentHTML('beforeend', `<div class="lp-ajax-message error" style="display:block">${error.message || 'Error: Query lp/v1/certificate/items-profile'}</div>`);
    }
    skeleton && skeleton.remove();
  };
  const getImageCertificate = certKey => {
    const listItemCertificate = document.querySelectorAll('.learnpress-certificates-profile .certificate-item');
    if (!listItemCertificate.length) return;
    listItemCertificate.forEach(item => {
      const eleConfig = item.querySelector('input.lp-data-config-cer');
      const dataConfig = JSON.parse(eleConfig.value);
      const elemParent = item.querySelector('.certificate-preview');
      if (elemParent === null) return;
      const key = elemParent.dataset.key;
      if (!certKey.includes(key)) return;
      LP_Certificate(elemParent, dataConfig);
      eleConfig.dataset.value = '';
    });
  };
  const showMoreReview = async (filter, ele, btnLoadReview = false) => {
    try {
      const response = await wp.apiFetch({
        path: addQueryArgs('lp/v1/certificate/items-profile', filter),
        method: 'GET'
      });
      const {
        data,
        status,
        message
      } = response;
      if (status === 'success' && data) {
        ele.innerHTML += data.template;
      } else {
        ele.innerHTML += `<li class="lp-ajax-message error" style="display:block">${message}</li>`;
      }
      if (btnLoadReview) {
        btnLoadReview.classList.remove('loading');
        const paged = btnLoadReview.dataset.paged;
        const numberPage = btnLoadReview.dataset.number;
        if (numberPage <= paged) {
          btnLoadReview.remove();
        }
        btnLoadReview.dataset.paged = parseInt(paged) + 1;
      }
      getImageCertificate(data.certKey);
    } catch (error) {
      ele.innerHTML += `<li class="lp-ajax-message error" style="display:block">${error}</li>`;
    }
  };
  document.addEventListener('click', function (e) {
    const btnLoadReview = document.querySelector('#certificates-load-more');
    if (btnLoadReview && btnLoadReview.contains(e.target)) {
      btnLoadReview.classList.add('loading');
      const paged = btnLoadReview && btnLoadReview.dataset.paged;
      filter.page = paged;
      const element = document.querySelector('#profile-content-certificates .profile-certificates');
      showMoreReview({
        ...filter
      }, element, btnLoadReview);
    }
  });
  skeletonTab();
};
document.addEventListener('DOMContentLoaded', function () {
  showListCertificates();
});
/******/ })()
;
//# sourceMappingURL=profile.certificates.js.map