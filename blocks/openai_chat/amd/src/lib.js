var questionString = 'Can I help u...'
var errorString = 'An error occurred! Please try again later.'
import * as Modal from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
export const init = (data) => {
    const blockId = data['blockId']
    const history = data['history']
    for (let message of history) {
        console.log("His: ", message)
        addToChatLog(message.role === 1 ? 'user' : 'bot', message.content,1 )
    }

    // Prevent sidebar from closing when osk pops up (hack for MDL-77957)
    window.addEventListener('resize', event => {
        event.stopImmediatePropagation();
    }, true);

    document.querySelector('#openai_input').addEventListener('keyup', e => {
        if (e.which === 13 && e.target.value !== "") {
            addToChatLog('user', e.target.value, 0)
            createCompletion(e.target.value, blockId)
            e.target.value = ''
        }
    })
    document.querySelector('.block_openai_chat #go').addEventListener('click', e => {
        const input = document.querySelector('#openai_input')
        if (input.value !== "") {
            addToChatLog('user', input.value, 0)
            createCompletion(input.value, blockId)
            input.value = ''
        }
    })

    document.querySelector('.block_openai_chat #refresh').addEventListener('click', e => {
        e.preventDefault();
        Modal.create({
        type: Modal.types.SAVE_CANCEL,
        title: 'Confirmation before refresh',
        body: '<p>Are you sure before you choose to refresh this chat?</p>',
        }).then(modal=>{
            modal.show();
            modal.getRoot().on(ModalEvents.save, () => {
                console.log("Click save")
                clearHistory(blockId);
                console.log("save successful!")
                modal.hide();
            });
            modal.getRoot().on(ModalEvents.hidden, function() {
                modal.destroy();
            });
        }).catch(Notification.exception);
    })

    require(['core/str'], function(str) {
        var strings = [
            {
                key: 'askaquestion',
                component: 'block_openai_chat'
            },
            {
                key: 'erroroccurred',
                component: 'block_openai_chat'
            },
        ];
        str.get_strings(strings).then((results) => {
            questionString = results[0];
            errorString = results[1];
        });
    });
}

/**
 * Add a message to the chat UI
 * @param {string} type Which side of the UI the message should be on. Can be "user" or "bot"
 * @param {string} message The text of the message to add
 */
const addToChatLog = (type, message, his) => {
    console.log("Message begin addtochatlog", message)
    let messageContainer = document.querySelector('#openai_chat_log')
    const messageElem = document.createElement('div')
    messageElem.classList.add('openai_message')
    for (let className of type.split(' ')) {
        messageElem.classList.add(className)
    }

    const messageText = document.createElement('span')
    if (his){
        messageText.innerText = message
    }
    else {
        messageText.innerHTML = message
    }

    messageElem.append(messageText)
    console.log(messageText)

    messageContainer.append(messageElem)
    if (messageText.offsetWidth) {
        messageElem.style.width = (messageText.offsetWidth + 40) + "px"
    }
    messageContainer.scrollTop = messageContainer.scrollHeight
}

const clearHistory = (blockId) => {

    fetch(`${M.cfg.wwwroot}/blocks/openai_chat/api/completion.php?block_id=${blockId}`, {
        method: 'DELETE',       
    })
    .then(response => {

        try{
            if (!response.ok) {
                throw Error(response.statusText)
            } else {
                document.querySelector('#openai_chat_log').innerHTML = ""
                return
            }
        } catch{
            console.log(error)
        }
    })
    .catch(error => {
        console.log(error)
        document.querySelector('#openai_input').classList.add('error')
        document.querySelector('#openai_input').placeholder = errorString
    })
}

/**
 * Makes an API request to get a completion from GPT-3, and adds it to the chat log
 * @param {string} message The text to get a completion for
 * @param {int} blockId The ID of the block this message is being sent from -- used to override settings if necessary
 * @param {string} api_type "assistant" | "chat" The type of API to use
 */
const createCompletion = (message, blockId) => {
    
    document.querySelector('.block_openai_chat #control_bar').classList.add('disabled')
    document.querySelector('#openai_input').classList.remove('error')
    document.querySelector('#openai_input').placeholder = questionString
    document.querySelector('#openai_input').blur()
    addToChatLog('bot loading', '...', 0);
    fetch(`${M.cfg.wwwroot}/blocks/openai_chat/api/completion.php`, {
        method: 'POST',
        body: JSON.stringify({
            message: message,
            blockId: blockId,
        })
    })
    .then(response => {
        let messageContainer = document.querySelector('#openai_chat_log')
        messageContainer.removeChild(messageContainer.lastElementChild)
        document.querySelector('.block_openai_chat #control_bar').classList.remove('disabled')
        console.log("response: ",response)
        if (!response.ok) {
            throw Error(response.statusText)
        } else {
            return response.json()
        }
    })
    .then(data => {
        console.log("data: ", data)
        try {
            addToChatLog('bot', data.message, 0)
        } catch (error) {
            console.log(error)
        }
        document.querySelector('#openai_input').focus()
    })
    .catch(error => {
        document.querySelector('#openai_input').classList.add('error')
        document.querySelector('#openai_input').placeholder = errorString
    })
    
}

/**
 * Using the existing messages in the chat history, create a string that can be used to aid completion
 * @return {JSONObject} A transcript of the conversation up to this point
 */

// const buildTranscript = () => {
//     let transcript = []
//     document.querySelectorAll('.openai_message').forEach((message, index) => {
//         if (index === document.querySelectorAll('.openai_message').length - 1) {
//             return
//         }

//         let user = userName
//         if (message.classList.contains('bot')) {
//             user = assistantName
//         }
//         transcript.push({"user": user, "message": message.innerText})
//     })
//     return transcript
// }
