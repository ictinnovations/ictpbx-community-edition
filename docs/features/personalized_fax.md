How to use Personalized Fax
===========================
The updated version of ICTFax supports sending personalized faxes. This  can be done with the support of a token as mentioned in the following. Each outgoing fax (either from Email, Web or from a Campaign) will replace tokens embedded in the Fax document with the corresponding value from current contact.

Note 1: This feature is only compatible with PDF file format, image based attachments can't use this feature.
Note 2: Only plain PDFs are supported, encoded, encrypted pdf will not work.

### For example
if we have the following in our fax attachment/pdf

     Hi [transmission:contact:first_name] [transmission:contact:last_name]

    You have [transmission:contact:custom1] usd due 
    against your phone number [transmission:contact:phone]

    Regards

    Yours sincerely,
    [transmission:account:first_name] 
    [transmission:account:last_name]

    then it will send the following message, personalized to the recipient.
    Hi Kashif Majeed

    You have 5 usd due
    against your phone number 923330000000

    Regards
    
    Yours sincerely,
    Nasir Iqbal

### Available Tokens
#### recipient information
* [transmission:contact:first_name]
* [transmission:contact:last_name]
* [transmission:contact:phone]
* [transmission:contact:email]
* [transmission:contact:address]
* [transmission:contact:custom1]
* [transmission:contact:custom2]
* [transmission:contact:custom3]
* [transmission:contact:description]

#### sender information
* [transmission:account:first_name]
* [transmission:account:last_name]
* [transmission:account:phone]
* [transmission:account:email]
* [transmission:account:address]
* [transmission:account:username]

