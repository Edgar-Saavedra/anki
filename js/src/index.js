

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
        `, 
    }
}).then((result) => {
    console.log('finished styling', result);
    ankiInvoke('createDeck', 6, {deck: 'test1'})
    .then((result) => {
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
    });
});
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

