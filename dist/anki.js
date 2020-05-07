/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./js/src/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./js/src/index.js":
/*!*************************!*\
  !*** ./js/src/index.js ***!
  \*************************/
/*! no static exports found */
/***/ (function(module, exports) {

eval("function ankiInvoke(action, version, params = {}) {\n  return new Promise((resolve, reject) => {\n    const xhr = new XMLHttpRequest();\n    xhr.addEventListener('error', () => reject('failed to issue request'));\n    xhr.addEventListener('load', () => {\n      try {\n        const response = JSON.parse(xhr.responseText);\n\n        if (Object.getOwnPropertyNames(response).length != 2) {\n          throw 'response has an unexpected number of fields';\n        }\n\n        if (!response.hasOwnProperty('error')) {\n          throw 'response is missing required error field';\n        }\n\n        if (!response.hasOwnProperty('result')) {\n          throw 'response is missing required result field';\n        }\n\n        if (response.error) {\n          throw response.error;\n        }\n\n        resolve(response.result);\n      } catch (e) {\n        reject(e);\n      }\n    });\n    xhr.open('POST', 'http://localhost:8765');\n    xhr.send(JSON.stringify({\n      action,\n      version,\n      params\n    }));\n  });\n}\n\nankiInvoke('updateModelStyling', 6, {\n  model: {\n    \"name\": \"Basic\",\n    \"css\": `\n            .card {\n                font-family: arial;\n                font-size: 20px;\n                text-align: left;\n                color: black;\n                background-color: white;\n            }\n            .card img {\n                max-width: 100%;\n            }\n            .card iframe {\n                max-width: 100%;\n            }\n        `\n  }\n}).then(result => {\n  console.log('finished styling', result);\n  ankiInvoke('createDeck', 6, {\n    deck: 'test1'\n  }).then(result => {\n    const notes = [];\n    Array.prototype.forEach.call(notesData, (value, key) => {\n      notes.push({\n        \"deckName\": \"test1\",\n        \"modelName\": \"Basic\",\n        \"fields\": {\n          \"Front\": `<h1>${value[\"Verb\"].value}</h1>\n                        <ul>\n                         <li>ich: ${value[\"ich\"].value}</li>\n                         <li>du: ${value[\"du\"].value}</li>\n                         <li>sie/er/es: ${value[\"du\"].value}</li>\n                         <li>wir: ${value[\"wir\"].value}</li>\n                         <li>ihr: ${value[\"ihr\"].value}</li>\n                         <li>sie/Sie: ${value[\"sie/Sie\"].value}</li>\n                        </ul>`,\n          \"Back\": `\n                        ${value[\"Translation\"].value}<br>\n                        ${value[\"image\"].value ? `<img src=\"${value[\"image\"].value}\">` : ''}\n                        ${value[\"youtube\"].value ? `\n                            <br/><iframe width=\"560\" height=\"315\" src=\"${value[\"youtube\"].value}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>` : ''}\n                    `,\n          \"slug\": `${value[\"Verb\"].value}`\n        },\n        \"options\": {\n          \"allowDuplicate\": false,\n          \"duplicateScope\": \"deck\"\n        },\n        \"tags\": [\"verbes\"]\n      });\n    });\n    ankiInvoke('addNotes', 6, {\n      \"notes\": notes\n    }).then(result => {\n      console.log(result);\n    });\n  });\n}); // .then((result) => {\n//     console.log(result);\n//     ankiInvoke('modelNamesAndIds', 6)\n//     .then((result) => {\n//         console.log(result);\n//     })\n//     ankiInvoke('canAddNotes', 6, {\n//         \"notes\": [\n//             {\n//                 \"deckName\": \"test1\",\n//                 \"modelName\": \"Basic\",\n//                 \"fields\": {\n//                     \"Front\": \"front content\",\n//                     \"Back\": \"back content\"\n//                 },\n//                 \"tags\": [\n//                     \"yomichan\"\n//                 ]\n//             }\n//         ]\n//     })\n//     .then((result) => {\n//         console.log(result);\n//     })\n//     ankiInvoke('addNotes', 6, {\n//         \"notes\": [\n// {\n//     \"deckName\": \"test1\",\n//     \"modelName\": \"Basic\",\n//     \"fields\": {\n//         \"Front\": `\n//             <h1>front content</h1>\n//             <p>Lots of html <br/> <img src=\"https://cnet3.cbsistatic.com/img/Z1VxjOHwlmzOW-QfGIL3sWFptT4=/1200x675/2019/11/30/7a76bca2-defb-4311-84b7-e90638487f82/twitter-in-stream-wide-baby-yoda-soup-mandalorian.jpg\"/></p>\n//         `,\n//         \"Back\": \"back content\"\n//     },\n//     \"options\": {\n//         \"allowDuplicate\": false,\n//         \"duplicateScope\": \"deck\"\n//     },\n//     \"tags\": [\n//         \"yomichan\"\n//     ]\n//     // \"audio\": [{\n//     //     \"url\": \"https://assets.languagepod101.com/dictionary/japanese/audiomp3.php?kanji=猫&kana=ねこ\",\n//     //     \"filename\": \"yomichan_ねこ_猫.mp3\",\n//     //     \"skipHash\": \"7e2c2f954ef6051373ba916f000168dc\",\n//     //     \"fields\": [\n//     //         \"Front\"\n//     //     ]\n//     // }]\n// }\n//         ]\n//     }).then((result) => {\n//         console.log(result);\n//     })\n// })\n\n//# sourceURL=webpack:///./js/src/index.js?");

/***/ })

/******/ });