window.document.addEventListener('DOMContentLoaded', () => {
    const scriptTag = document.querySelector('script[src$="safe_password_reset.js"]');
    const translations = JSON.parse(scriptTag.dataset.translations);

    function translate(key) {
        return translations[key] || key
    }

    const passwordResetForm = document.querySelector('form[action*="/password/reset"]');
    const submitPasswordResetBtn = passwordResetForm.querySelector('button[type="submit"]')
    const passwordResetEmailInput = passwordResetForm.querySelector('input[type="email"]')

    passwordResetEmailInput.required = true

    const sentToPhoneDiv = document.createElement('p')
    sentToPhoneDiv.id = 'sentToPhoneInfo'
    sentToPhoneDiv.className = 'text-center'
    passwordResetForm.appendChild(sentToPhoneDiv)

    async function request_reset_password_notification() {
        submitPasswordResetBtn.innerHTML += '<div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Loading...</span></div>'
        submitPasswordResetBtn.disabled = true

        const email = passwordResetEmailInput.value

        const res = await fetch(`/modules/addons/lknhooknotification/src/Core/api.php?endpoint=password/reset?email=${email}`)
            .then(res => res.json())

        if (res.exceeded_try) {
            sentToPhoneDiv.innerHTML = translate('You have reached the maximum limit of requests. Check your email inbox or SPAM folder or the WhatsApp registered in your profile.')

            submitPasswordResetBtn.parentElement.remove()
            passwordResetEmailInput.parentElement.parentElement.remove()

            return
        }

        const sentToPhone = res?.sent_to_phone
        const sentToEmail = res?.sent_to_email

        const renderSentTo = (label, ...values) => {
            sentToPhoneDiv.textContent = ''
            sentToPhoneDiv.appendChild(document.createTextNode(label))
            for (const value of values) {
                sentToPhoneDiv.appendChild(document.createElement('br'))
                sentToPhoneDiv.appendChild(document.createTextNode(value))
            }
        }

        if (Object.keys(res).length === 0) {
            sentToPhoneDiv.textContent = translate('An error occurred!') + ' '
            const retryLink = document.createElement('a')
            retryLink.href = ''
            retryLink.textContent = translate('Try again')
            sentToPhoneDiv.appendChild(retryLink)
        } else if (sentToPhone && sentToEmail) {
            renderSentTo(translate('Sent to WhatsApp and email:'), sentToPhone, sentToEmail)
        } else if (sentToPhone) {
            renderSentTo(translate('Sent to WhatsApp:'), sentToPhone)
        } else if (sentToEmail) {
            renderSentTo(translate('Sent to email:'), sentToEmail)
        }

        submitPasswordResetBtn.parentElement.remove()
        passwordResetEmailInput.parentElement.parentElement.remove()
    }

    submitPasswordResetBtn.addEventListener('click', async (e) => {
        e.preventDefault()

        if (!passwordResetForm.checkValidity()) {
            passwordResetForm.reportValidity()
            return
        }

        await request_reset_password_notification()
    })
})
