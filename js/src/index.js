
const GERMAN_DECK = 'german-conjugations';
const FRENCH_DECK = 'french-conjugations';

var myHeaders = new Headers();
myHeaders.set('Content-Type','application/json')
var myInit = { method: 'GET',
              headers: myHeaders,
              mode: 'cors',
              cache: 'default' };

fetch('/french-conjugations-verbix.json',myInit)
.then(function(response) {
  var contentType = response.headers.get("content-type");
  if(contentType && contentType.indexOf("application/json") !== -1) {
    return response.json();
  } else {
    console.log("Oops, nous n'avons pas du JSON!");
    return false;
  }
})
.then(function(json) {
    if(json)
        console.log(json);
})

function ankiInvoke(action, version, params={}) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.addEventListener('error', () => reject('failed to issue request'));
        xhr.addEventListener('load', () => {
            try {
                const response = JSON.parse(xhr.responseText);
                if (Object.getOwnPropertyNames(response).length != 2) {
                    throw 'response has an unexpected number of fields';
                }
                if (!response.hasOwnProperty('error')) {
                    throw 'response is missing required error field';
                }
                if (!response.hasOwnProperty('result')) {
                    throw 'response is missing required result field';
                }
                if (response.error) {
                    throw response.error;
                }
                resolve(response.result);
            } catch (e) {
                reject(e);
            }
        });

        xhr.open('POST', 'http://localhost:8765');
        xhr.send(JSON.stringify({action, version, params}));
    });
}

const addAnkiCards = (type, data) => {
    ankiInvoke('updateModelStyling', 6, {
        model: {
            "name": "Basic",
            "css": `
                .card {
                    font-family: arial;
                    font-size: 20px;
                    text-align: left;
                    color: black;
                    background-color: white;
                }
                .card img {
                    max-width: 100%;
                }
                .card iframe {
                    max-width: 100%;
                }
                .card table {
                    width: 100%;
                }
                .card table, th, td {
                    border: 1px solid black;
                }
                .card table th {
                    text-align: left;
                    background: grey;
                    color: white;
                    padding: 5px;
                }
            `, 
        }
    }).then((result) => {
        switch(type) {
            case 'babGerman':
                ankiInvoke('createDeck', 6, {deck: GERMAN_DECK})
                .then((result) => {
                    createGermanConjugatedNotes(data);
                });
            break
            case 'babFrench':
                ankiInvoke('createDeck', 6, {deck: FRENCH_DECK})
                .then((result) => {
                    createFrenchConjugatedNotes(data);
                });
            break;
        }
        console.log('finished styling', result);
    });
}


const objectToHtml = (obj) => {
    let str = ``;
    // tenses
    for (const property in obj.tenses) {
        const inner_obj = obj.tenses[property];
        let tense = null;
        let rows = ``;
        let columncount = 0;
        // conjugations
        for(const inner_property in inner_obj.conjugations) {
            tense = inner_obj.conjugations[inner_property].tense;
            columncount++;
            rows += `
            <td>
            <h5>${inner_obj.conjugations[inner_property].person}</h5>
            <p>${inner_obj.conjugations[inner_property].result}</p>
            </td>
            `;
        }
        let table = `<table><thead><tr>
            <th colspan="${columncount}">${tense}</th>
        </tr></thead>
        <tbody>
        <tr>${rows}</tr></tbody></table>`;
        str += table;
    }
    return str;
};


const createGermanConjugatedNotes = (conjugationGermanData = null) => {
    const notes = [];
    if(typeof conjugationGermanData !== 'undefined') {
        Array.prototype.forEach.call(conjugationGermanData, (value, key) => {
            if(value['conjugations_found']) {
                const back = `
                ${value['translation'] ? `<h2>${value['translation']}</h2>` : ''}
                <hr/>
                ${value['indikativ']? objectToHtml(value['indikativ']) : ''}
                <hr/>
                ${value['konjuktiv']? objectToHtml(value['konjuktiv']) : ''}
                <hr/>
                ${value['imperativ']? objectToHtml(value['imperativ']) : ''}
                <hr/>
                ${value['partizip']? objectToHtml(value['partizip']) : ''}
                `;
                // document.querySelectorAll('body')[0].innerHTML = back;
                notes.push(
                    {
                    "deckName": GERMAN_DECK,
                    "modelName": "Basic",
                    "fields": {
                        "Front": `<h1>${value["verb"]}</h1>`,
                        "Back": back,
                        "slug": `${value["verb"]}`
                    },
                    "options": {
                        "allowDuplicate": false,
                        "duplicateScope": "deck"
                    },
                    "tags": [
                        "verbes"
                    ]
                    }
                );
            } else {
                console.log('Conjugation Not Found', value);
            }
        });
        ankiInvoke('addNotes', 6, {
            "notes": notes
        })
        .then((result) => {
            console.log('german notes created', result);
        })
    }
};


const createFrenchConjugatedNotes = (conjugationFrenchData = null) => {
    const notes = [];
    if(typeof conjugationFrenchData !== 'undefined') {
        Array.prototype.forEach.call(conjugationFrenchData, (value, key) => {
            if(value['conjugations_found']) {
                const back = `
                ${value['translation'] ? `<h2>${value['translation']}</h2>` : ''}
                <hr/>
                ${value['indicatif']? objectToHtml(value['indicatif']) : ''}
                <hr/>
                ${value['subjonctif']? objectToHtml(value['subjonctif']) : ''}
                <hr/>
                ${value['conditionnel']? objectToHtml(value['conditionnel']) : ''}
                <hr/>
                ${value['imperatif']? objectToHtml(value['imperatif']) : ''}
                `;
                // document.querySelectorAll('body')[0].innerHTML = back;
                notes.push(
                    {
                    "deckName": FRENCH_DECK,
                    "modelName": "Basic",
                    "fields": {
                        "Front": `<h1>${value["verb"]}</h1>`,
                        "Back": back,
                        "slug": `${value["verb"]}`
                    },
                    "options": {
                        "allowDuplicate": false,
                        "duplicateScope": "deck"
                    },
                    "tags": [
                        "verbes"
                    ]
                    }
                );
            } else {
                console.log('Conjugation Not Found', value);
            }
        });
        ankiInvoke('addNotes', 6, {
            "notes": notes
        })
        .then((result) => {
            console.log('french notes created', result);
        })
    }
};

const createExampleNote = () => {
    const notes = [];
    Array.prototype.forEach.call(notesData, (value, key) => {
        notes.push(
            {
            "deckName": "test1",
            "modelName": "Basic",
            "fields": {
                "Front": `<h1>${value["Verb"].value}</h1>
                    <ul>
                        <li>ich: ${value["ich"].value}</li>
                        <li>du: ${value["du"].value}</li>
                        <li>sie/er/es: ${value["du"].value}</li>
                        <li>wir: ${value["wir"].value}</li>
                        <li>ihr: ${value["ihr"].value}</li>
                        <li>sie/Sie: ${value["sie/Sie"].value}</li>
                    </ul>`,
                "Back": `
                    ${value["Translation"].value}<br>
                    ${value["image"].value ? `<img src="${value["image"].value}">`: ''}
                    ${value["youtube"].value ? `
                        <br/><iframe width="560" height="315" src="${value["youtube"].value}" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`: ''}
                `,
                "slug": `${value["Verb"].value}`
            },
            "options": {
                "allowDuplicate": false,
                "duplicateScope": "deck"
            },
            "tags": [
                "verbes"
            ]
            }
        );
    });
    ankiInvoke('addNotes', 6, {
        "notes": notes
    })
    .then((result) => {
        console.log(result);
    })
}
// .then((result) => {
//     console.log(result);
//     ankiInvoke('modelNamesAndIds', 6)
//     .then((result) => {
//         console.log(result);
//     })
    
//     ankiInvoke('canAddNotes', 6, {
//         "notes": [
//             {
//                 "deckName": "test1",
//                 "modelName": "Basic",
//                 "fields": {
//                     "Front": "front content",
//                     "Back": "back content"
//                 },
//                 "tags": [
//                     "yomichan"
//                 ]
//             }
//         ]
//     })
//     .then((result) => {
//         console.log(result);
//     })
//     ankiInvoke('addNotes', 6, {
//         "notes": [
            // {
            //     "deckName": "test1",
            //     "modelName": "Basic",
            //     "fields": {
            //         "Front": `
            //             <h1>front content</h1>
            //             <p>Lots of html <br/> <img src="https://cnet3.cbsistatic.com/img/Z1VxjOHwlmzOW-QfGIL3sWFptT4=/1200x675/2019/11/30/7a76bca2-defb-4311-84b7-e90638487f82/twitter-in-stream-wide-baby-yoda-soup-mandalorian.jpg"/></p>
            //         `,
            //         "Back": "back content"
            //     },
            //     "options": {
            //         "allowDuplicate": false,
            //         "duplicateScope": "deck"
            //     },
            //     "tags": [
            //         "yomichan"
            //     ]
            //     // "audio": [{
            //     //     "url": "https://assets.languagepod101.com/dictionary/japanese/audiomp3.php?kanji=猫&kana=ねこ",
            //     //     "filename": "yomichan_ねこ_猫.mp3",
            //     //     "skipHash": "7e2c2f954ef6051373ba916f000168dc",
            //     //     "fields": [
            //     //         "Front"
            //     //     ]
            //     // }]
            // }
//         ]
//     }).then((result) => {
//         console.log(result);
//     })
// })

