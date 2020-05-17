import React from 'react';
import {
  Conjugation
} from "./Conjugation";
import {
  Word
} from "./Word";
import {
  ankiInvoke
} from "./helpers";
import { renderToString } from 'react-dom/server'

function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
}

export class App extends React.Component {
  constructor(props) {
    super(props);
    this.state = { 
      loadingVerbData: false,
      loadingWordData: false,
      searchWord:null,
      searchVerb: null
    };
  }
  searchWordSpace=(event)=>{
    const that = this;
    let keyword = event.target.value;
    that.setState({searchWord:keyword});
  }
  searchVerbSpace=(event)=>{
    const that = this;
    let keyword = event.target.value;
    that.setState({searchVerb:keyword});
  }
  loadWordTabels() {
    if(this.props.wordData) {
      return <div>
      
      <h1>Word Data</h1>
      <input type="text" placeholder="Enter item to be searched" onChange={(e)=>this.searchWordSpace(e)} />
      <hr/>
      <button onClick={this.uploadWordData.bind(this)}>Upload All This Beautiful Word Data!</button>
      {
        this.state.loadingWordData ? <div>Your data is being sent to anki! Just a sec this can take a while...</div>: ''
      }
      {
        Object.keys(this.props.wordData).filter((data)=>{
          if(this.state.searchWord == null)
              return data
          else if(data.toLowerCase().includes(this.state.searchWord.toLowerCase()) ){
              return data
          }
        }).map((key, index) => {
          return <Word element={this.props.wordData[key]} key={key} elementKey={key}></Word>;
        })
      }
    </div>
    }
  }
  loadVerbTabels() {
    if(this.props.verbData) {
      return <div>
      <h1>Verb Data</h1>
      <input type="text" placeholder="Enter item to be searched" onChange={(e)=>this.searchVerbSpace(e)} />
      <hr/>
      <button onClick={this.uploadVerbData.bind(this)}>Upload All This Beautiful Verb Data!</button>
      {
        this.state.loadingVerbData ? <div>Your data is being sent to anki! Just a sec this can take a while...</div>: ''
      }
      {
        Object.keys(this.props.verbData).filter((data)=>{
          if(this.state.searchVerb == null)
              return data
          else if(data.toLowerCase().includes(this.state.searchVerb.toLowerCase()) ){
              return data
          }
        }).map((key, index) => {
          return <Conjugation wordData={this.props.wordData} element={this.props.verbData[key]} key={key} elementKey={key}></Conjugation>;
        })
      }
    </div>
    }
  }
  uploadWordData() {
    const wordData = this.props.wordData;
    const deck = `${this.props.deck}-words`;
    const that = this;
    that.setState((state, props) => {
      return {
        loadingWordData: true
      }
    });
    ankiInvoke('createDeck', 6, {deck: deck})
    .then((result) => {
      console.log('deck created', result);
      const notes = [];
      Object.keys(that.props.wordData).map((key, index) => {
        const element = that.props.wordData[key];
        if(element.word) {
          notes.push(
            {
              "deckName": deck,
              "modelName": "Basic",
              "fields": {
                  "Front": `<h1>${element.word}</h1>`,
                  "Back": renderToString(<Word element={element} key={index}></Word>),
                  "slug": `${element.word}`
              },
              "options": {
                  "allowDuplicate": false,
                  "duplicateScope": "deck"
              },
              "tags": [
                  "words"
              ]
            }
          );
        }
      });
      ankiInvoke('addNotes', 6, {
        "notes": notes
      })
      .then((result) => {
          console.log(`your ${deck} notes were created!`, result);
          console.log('all the beautiful data that was added', wordData);
          that.setState((state, props) => {
            return {
              loadingWordData: false
            }
          });
      })
    });
  }
  uploadVerbData() {
    const verbData = this.props.verbData;
    const deck = `${this.props.deck}-conjugations`;
    const that = this;
    that.setState((state, props) => {
      return {
        loadingVerbData: true
      }
    });
    ankiInvoke('createDeck', 6, {deck: deck})
    .then((result) => {
      console.log('deck created', result);
      const notes = [];
      Object.keys(that.props.verbData).map((key, index) => {
        const element = that.props.verbData[key];
        notes.push(
          {
            "deckName": deck,
            "modelName": "Basic",
            "fields": {
                "Front": `<h1>${element["verb"]}</h1>`,
                "Back": renderToString(<Conjugation wordData={that.props.wordData} element={element} key={index} elementKey={key}></Conjugation>),
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
          that.setState((state, props) => {
            return {
              loadingVerbData: false
            }
          });
          console.log(`your ${deck} notes were created!`, result);
          console.log('all the beautiful data that was added', verbData);
      })
    });
  }
  render() {
    return <div>
      {this.loadWordTabels()}
      {this.loadVerbTabels()}
    </div>;
  }
}
