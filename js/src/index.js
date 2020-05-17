import {
  GERMAN_DECK,
  getJson,
  FRENCH_DECK
} from "./helpers";
import React from 'react';
import ReactDOM from 'react-dom';
import {
  App
} from "./App";

const gVerbs = getJson("/german-conjugations-verbix.json");
const gWords = getJson("/german-definitions-dwds.json");
const fVerbs = getJson("/french-conjugations-larousse.json");
const fWords = getJson("/german-french-larousse.json");


Promise.all([
  gVerbs,
  gWords,
  fVerbs,
  fWords
]).then((data) => {
  if(data) {
    ReactDOM.render(
      <App wordData={data[1]} deck={GERMAN_DECK}/>, 
      document.querySelector('#app'));
    console.log(Object.keys(data[1]).length, data[1]);
    // console.log(data[3]);
    // ReactDOM.render(
    //   <App verbData={data[2]} wordData={data[3]} deck={FRENCH_DECK}/>, 
    //   document.querySelector('#app'));
  }
})
