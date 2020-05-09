import {
  GERMAN_DECK,
  getJson,
  ankiInvoke
} from "./helpers";
import React from 'react';
import ReactDOM from 'react-dom';
import { renderToString } from 'react-dom/server'
import {
  App
} from "./App";
import {
  Conjugation
} from "./Conjugation";

getJson("/german-conjugations-verbix.json")
.then((data) => {
  if(data) {
    ReactDOM.render(
      <App germanData={data}/>, 
      document.querySelector('#app'));
    ankiInvoke('createDeck', 6, {deck: GERMAN_DECK})
    .then((result) => {
      console.log('deck created', result);
      const notes = [];
      data.map((element, key) => {
        notes.push(
          {
            "deckName": GERMAN_DECK,
            "modelName": "Basic",
            "fields": {
                "Front": `<h1>${element["verb"]}</h1>`,
                "Back": renderToString(<Conjugation element={element} key={key}></Conjugation>),
                "slug": `${element["verb"]}`
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
          console.log('german notes created', result);
      })
    });
  }
});
