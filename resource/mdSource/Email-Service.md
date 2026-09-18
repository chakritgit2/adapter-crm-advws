> ## Documentation Index
> Fetch the complete documentation index at: https://developers.smtp2go.com/llms.txt
> Use this file to discover all available pages before exploring further.

# Send an Email

Learn how to send a standard email using the API.

## Introducing Standard Email

> 📘 Note
>
> There are two ways to send emails through the API which include using the **Standard Email** ([/email/send](https://developers.smtp2go.com/reference/send-standard-email)) endpoint or using the **MIME Email** ([/email/mime](https://developers.smtp2go.com/reference/send-mime-email)) endpoint.
>
> This basic guide uses the Standard Email option.

With the [/email/send](https://developers.smtp2go.com/reference/send-standard-email) endpoint, you pass us all of the components of an email, such as the *sender*, *body* and *recipient* as a JSON Object. We then create and send the email. The endpoint also allows you to make use of email templates - our [Get Started with Templates](https://developers.smtp2go.com/docs/getting-started-with-templates) guide is a great place to start.

The [/email/send](https://developers.smtp2go.com/reference/send-standard-email) endpoint accepts 13 parameters with 3 being required (marked with \*) - sender (string), to (array of strings) and subject (string). All parameters are:

> 👍 Three required parameters
>
> Sender (string), To (array of strings) and Subject (string) are the required parameters. Marked below with \*.

| Parameter       | Type             | Description                                                                                                                    |
| :-------------- | :--------------- | :----------------------------------------------------------------------------------------------------------------------------- |
| sender\*        | string           | The email address to send from                                                                                                 |
| to\*            | array of strings | An array of email addresses (up to 100) to send to                                                                             |
| cc              | array of strings | An array of email addresses (up to 100) to cc                                                                                  |
| bcc             | array of strings | An array of email addresses (up to 100) to bcc                                                                                 |
| subject\*       | string           | The subject of the email to be sent                                                                                            |
| html\_body      | string           | An HTML encoded email body                                                                                                     |
| text\_body      | string           | A plain text email body                                                                                                        |
| custom\_headers | array of objects | An array of custom header objects to be applied to the email                                                                   |
| attachments     | array of objects | An array of attachment objects to be attached to the email                                                                     |
| inlines         | array of objects | An array of images to be inlined into the email                                                                                |
| template\_id    | string           | The ID of the template you wish to use                                                                                         |
| template\_data  | json             | When a template\_id is provided, include the pass-through values in the format \{"variable1": "value1", "variable2": "value2"} |

<br />

## Craft a simple message

In this example, we'll POST to the [https://api.smtp2go.com/v3/email/send](https://api.smtp2go.com/v3/email/send) endpoint. For our first email, we'll use the three required parameters, along with the *text\_body* parameter.

## The request structure

The HTTP POST request is made up of two parts, the request headers and the request body containing the email message payload in JSON.

The request headers include:

* Endpoint URL ([https://api.smtp2go.com/v3/email/send](https://api.smtp2go.com/v3/email/send)).
* Content type (application/json).
* API Key for authentication (via the X-Smtp2go-Api-Key header). Alternatively, you could pass the API Key in the request data ("api\_key": "YourAPIKeyHere").

Example:

```curl Request headers
curl --request POST \
     --url https://api.smtp2go.com/v3/email/send \
     --header 'Content-Type: application/json' \
     --header 'X-Smtp2go-Api-Key: api-xxxx YOUR API KEY xxxx' \
     --header 'accept: application/json' \
```

The request body contains the message payload as a JSON Object.

Example:

```Text Request body
     --data '
{
  "sender": "email@example.com",
  "to": [
    "friend@example.com"
  ],
  "subject": "My First Email",
  "text_body": "Hello from the other side."
}
'
```

## The full request

Include the headers covered in the example below. Remember to enter your unique API Key, set the sender/from address, recipient/to address and enter some text in the *text\_body* parameter.

Though this guide uses cURL for examples, a wide number of options from *C++*, to *Javascript, Node* and more can be accessed throughout our [API Reference](https://developers.smtp2go.com/reference) documentation.

> 🚧 Ensure your "sender" address is valid
>
> Enter an email address that your account is authorised to send from (an email address from a verified sender). Check out the [Getting Started with the API guide](https://developers.smtp2go.com/docs/getting-started#sender-verification) for details.

```curl
curl --request POST \
     --url https://api.smtp2go.com/v3/email/send \
     --header 'Content-Type: application/json' \
     --header 'X-Smtp2go-Api-Key: api-xxxxxxxxxxxxxxxxxx' \
     --header 'accept: application/json' \
     --data '
{
  "sender": "email@example.com",
  "to": [
    "friend@example.com"
  ],
  "subject": "My First Email",
  "text_body": "Hello from the other side."
}
'
```

## Responses

> 👍 Success!
>
> This will be indicated by **a 200 OK** response

Similar to the below:

```curl
{
  "request_id": "aa253464-0bd0-467a-b24b-6159dcd7be60",
  "data": {
    "succeeded": 1,
    "failed": 0,
    "failures": [],
    "email_id": "1er8bV-6Tw0Mi-7h"
  }
}
```

The email\_id can be logged into your system and used when searching for the email.

A **400** response is encountered if the request fails and will include an explanation of the error. Similar to:

```curl
 {
  "request_id": "22e5acba-43bf-11e6-ae42-408d5cce2644",
  "data": {
    "error_code": "E_ApiResponseCodes.ENDPOINT_PERMISSION_DENIED",
    "error": "You do not have permission to access this API endpoint"
  }
}
```

## Take it further

Once you are sending a simple text email successfully, you can try using the *html\_body* parameter to send emails with HTML content or [add attachments](https://developers.smtp2go.com/docs/adding-attachments) using the *attachments* parameter.

To make use of email templates, view our “Get Started With Templates” page using the link below.