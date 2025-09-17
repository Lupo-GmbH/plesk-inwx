# DomRobot XML-RPC API Documentation

## Chapter 2. Methods

### 2.15. Nameserver

The **nameserver** object provides methods to manage the nameserver domains and their records.

### 2.15.1. nameserver.check

Checks if the given nameservers are responding accordingly.

#### 2.15.1.1. Input

**Table 2.193. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | true |     |
| ns  | List of nameserver | [nsList](/en/help/apidoc/f/ch03.html#type.nslist "3.71. nsList") | true |     |



#### 2.15.1.2. Output

**Table 2.194. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| details |     |     |     |
| ... ns | Given nameserver | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") |     |
| ... messageList | Check messages | [array\_text255](/en/help/apidoc/f/ch03.html#type.array_text255 "3.11. array_text255") |     |
| ... status | Stauts of the nameserver check | [text64](/en/help/apidoc/f/ch03.html#type.text64 "3.99. text64") |     |



### 2.15.2. nameserver.clone

Clones cource domain DNS to target DNS.

#### 2.15.2.1. Input

**Table 2.195. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| sourceDomain | Source domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | true |     |
| targetDomain | Target domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | true |     |



#### 2.15.2.2. Output

**Table 2.196. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| roId | ID of new zone | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |



### 2.15.3. nameserver.create

Creates a domain in the nameserver.

#### 2.15.3.1. Input

**Table 2.197. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | true |     |
| type | Type of nameserver entry | [nsType](/en/help/apidoc/f/ch03.html#type.nstype "3.73. nsType") | true |     |
| ns  | List of nameserver | [nsList](/en/help/apidoc/f/ch03.html#type.nslist "3.71. nsList") | false |     |
| masterIp | Master IP address | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") | false |     |
| web | Web nameserver entry | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| mail | Mail nameserver entry | [text0255](/en/help/apidoc/f/ch03.html#type.text0255 "3.94. text0255") | false |     |
| soaEmail | Email address for SOA record | [email](/en/help/apidoc/f/ch03.html#type.email "3.43. email") | false |     |
| urlRedirectType | Type of the url redirection | [urlRedirectType](/en/help/apidoc/f/ch03.html#type.urlredirecttype "3.108. urlRedirectType") | false |     |
| urlRedirectTitle | Title of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectDescription | Description of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectFavIcon | FavIcon of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectKeywords | Keywords of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| testing | Execute command in testing mode | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |
| ignoreExisting | Ignore existing | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |



#### 2.15.3.2. Output

**Table 2.198. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| roId | Created DNS domain id | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |



### 2.15.4. nameserver.createRecord

Creates a new nameserver record.

#### 2.15.4.1. Input

**Table 2.199. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| roId | DNS domain id | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| type | Type of the nameserver record | [recordType](/en/help/apidoc/f/ch03.html#type.recordtype "3.81. recordType") | true |     |
| content | Content of the nameserver record | [text](/en/help/apidoc/f/ch03.html#type.text "3.91. text") | true |     |
| name | Name of the nameserver record | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| ttl | TTL (time to live) of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 3600 |
| prio | Priority of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 0   |
| urlRedirectType | Type of the url redirection | [urlRedirectType](/en/help/apidoc/f/ch03.html#type.urlredirecttype "3.108. urlRedirectType") | false |     |
| urlRedirectTitle | Title of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectDescription | Description of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectFavIcon | FavIcon of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectKeywords | Keywords of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlAppend | Append the path for redirection | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |
| testing | Execute command in testing mode | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |



#### 2.15.4.2. Output

**Table 2.200. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| id  | Created record id | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |



### 2.15.5. nameserver.delete

Deletes a nameserver domain

#### 2.15.5.1. Input

**Table 2.201. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| roId | Id (Repository Object Identifier) of the DNS domain | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| testing | Execute command in testing mode | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |



#### 2.15.5.2. Output

No additional return parameters

### 2.15.6. nameserver.deleteRecord

Deletes a nameserver record.

#### 2.15.6.1. Input

**Table 2.202. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| id  | Id of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | true |     |
| testing | Execute command in testing mode | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |



#### 2.15.6.2. Output

No additional return parameters

### 2.15.7. nameserver.export

Creates a nameserver.export TXT Datei

#### 2.15.7.1. Input

**Table 2.203. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | true |     |



#### 2.15.7.2. Output

**Table 2.204. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| export | export result | [text](/en/help/apidoc/f/ch03.html#type.text "3.91. text") |     |



### 2.15.8. nameserver.exportlist

Nameserver List export as file

#### 2.15.8.1. Input

**Table 2.205. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| format | Format of the requested document | [documentformat](/en/help/apidoc/f/ch03.html#type.documentformat "3.37. documentformat") | false | raw |
| domain | Search by domain name | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false | \*  |
| wide | More detailed output | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 1   |
| page | Page number for paging | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 1   |
| pagelimit | Max number of results per page | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 20  |



#### 2.15.8.2. Output

**Table 2.206. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| count | Log timestamp | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| domains | Domains Log list | [array](/en/help/apidoc/f/ch03.html#type.array "3.4. array") |     |



### 2.15.9. nameserver.exportrecords

Export nameserver records as file

#### 2.15.9.1. Input

**Table 2.207. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| format | Request output of given format. Defaults to csv. | [documentformat](/en/help/apidoc/f/ch03.html#type.documentformat "3.37. documentformat") | false | csv |
| name | Filter results by given record name. You can, for example, set domain names here. Wildcard \* is allowed. | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| page | If limit is set, show entries from {page - 1}\*{limit}. Defaults to 1. | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 1   |
| limit | Only return {limit} entries at once. Set to 0 for no limit. Defaults to 0. | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 0   |



#### 2.15.9.2. Output

**Table 2.208. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| count | Amount of entries returned. | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| data | Result file data in cleartext or base64 coded, depending on the selected format. CSV returns as clear text. Other formats are currently not supported. | [base64](/en/help/apidoc/f/ch03.html#type.base64 "3.17. base64") |     |



### 2.15.10. nameserver.info

Get nameserver record details. The request requires either the domain or roid.

#### 2.15.10.1. Input

**Table 2.209. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Search by domain name. The request requires either the domain or roid. | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| roId | Id (Repository Object Identifier) of the DNS domain. The request requires either the domain or roid. | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| recordId | Search by record id | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| type | Search by record type | [text64](/en/help/apidoc/f/ch03.html#type.text64 "3.99. text64") | false |     |
| name | Search by record name | [text0255](/en/help/apidoc/f/ch03.html#type.text0255 "3.94. text0255") | false |     |
| content | Search by record content | [text1024](/en/help/apidoc/f/ch03.html#type.text1024 "3.97. text1024") | false |     |
| ttl | Search by record ttl | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| prio | Search by record priority | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |



#### 2.15.10.2. Output

**Table 2.210. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| roId | Id (Repository Object Identifier) of the DNS domain | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | Yes |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | Yes |
| type | Type of nameserver domain | [nsType](/en/help/apidoc/f/ch03.html#type.nstype "3.73. nsType") | Yes |
| masterIp | Master IP address | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") | Yes |
| lastZoneCheck | Time of last zone check | [dateTime](/en/help/apidoc/f/ch03.html#type.datetime "3.30. dateTime") | Yes |
| slaveDns |     |     | Yes |
| ... name | Hostname of the nameserver | [hostname](/en/help/apidoc/f/ch03.html#type.hostname "3.53. hostname") |     |
| ... ip | Ip address of the nameserver | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") |     |
| SOAserial | SOA-RR serial | [text064](/en/help/apidoc/f/ch03.html#type.text064 "3.95. text064") | Yes |
| count | Total number of domain records | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | Yes |
| record |     |     | Yes |
| ... id | Id of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| ... name | Name of the nameserver record | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") |     |
| ... type | Type of the nameserver record | [recordType](/en/help/apidoc/f/ch03.html#type.recordtype "3.81. recordType") |     |
| ... content | Content of the nameserver record | [text1024](/en/help/apidoc/f/ch03.html#type.text1024 "3.97. text1024") |     |
| ... ttl | TTL (time to live) of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| ... prio | Priority of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| ... urlRedirectType | Type of the url redirection | [urlRedirectType](/en/help/apidoc/f/ch03.html#type.urlredirecttype "3.108. urlRedirectType") | Yes |
| ... urlRedirectTitle | Title of the frame redirection | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | Yes |
| ... urlRedirectDescription | Description of the frame redirection | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | Yes |
| ... urlRedirectKeywords | Keywords of the frame redirection | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | Yes |
| ... urlRedirectFavIcon | FavIcon of the frame redirection | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | Yes |
| ... urlAppend | Append the path to redirection | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | Yes |



### 2.15.11. nameserver.list

List all nameserver domains.

#### 2.15.11.1. Input

**Table 2.211. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Search by domain name | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false | \*  |
| wide | More detailed output | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 1   |
| page | Page number for paging | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 1   |
| pagelimit | Max number of results per page | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false | 20  |



#### 2.15.11.2. Output

**Table 2.212. Parameters**

| Parameter | Description | Type | Optional |
| --- | --- | --- | --- |
| count | Total number of nameserver domains | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| domains |     |     |     |
| ... roId | Id (Repository Object Identifier) of the DNS domain | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") |     |
| ... domain | Domain name | [array\_domain](/en/help/apidoc/f/ch03.html#type.array_domain "3.5. array_domain") |     |
| ... type | Type of nameserver domain | [nsType](/en/help/apidoc/f/ch03.html#type.nstype "3.73. nsType") |     |
| ... masterIp | Master ip address | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") | Yes |
| ... mail | Mail nameserver entry | [ip\_url](/en/help/apidoc/f/ch03.html#type.ip_url "3.59. ip_url") | Yes |
| ... web | Web nameserver entry | [ip\_url](/en/help/apidoc/f/ch03.html#type.ip_url "3.59. ip_url") | Yes |
| ... url | Web forwarding url | [ip\_url](/en/help/apidoc/f/ch03.html#type.ip_url "3.59. ip_url") | Yes |
| ... urlType | The redirect type of the forwarding url (only if \`url\` is set) | [urlRedirectType](/en/help/apidoc/f/ch03.html#type.urlredirecttype "3.108. urlRedirectType") | Yes |
| ... ipv4 | Web IPv4 address | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") | Yes |
| ... ipv6 | Web IPv6 address | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") | Yes |



### 2.15.12. nameserver.update

Updates a nameserver domain.

#### 2.15.12.1. Input

**Table 2.213. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| domain | Domain name | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| roId | Id (Repository Object Identifier) of the DNS domain | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| type | Type of nameserver entry | [nsType](/en/help/apidoc/f/ch03.html#type.nstype "3.73. nsType") | false |     |
| masterIp | Master ip address | [ip](/en/help/apidoc/f/ch03.html#type.ip "3.57. ip") | false |     |
| ns  | List of nameserver | [nsList](/en/help/apidoc/f/ch03.html#type.nslist "3.71. nsList") | false |     |
| web | Web nameserver entry | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| mail | Mail nameserver entry | [token255](/en/help/apidoc/f/ch03.html#type.token255 "3.103. token255") | false |     |
| urlRedirectType | Type of the url redirection | [urlRedirectType](/en/help/apidoc/f/ch03.html#type.urlredirecttype "3.108. urlRedirectType") | false |     |
| urlRedirectTitle | Title of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectDescription | Description of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectFavIcon | FavIcon of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectKeywords | Keywords of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| testing | Execute command in testing mode | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |



#### 2.15.12.2. Output

No additional return parameters

### 2.15.13. nameserver.updateRecord

Updates a nameserver record.

#### 2.15.13.1. Input

**Table 2.214. Parameters**

| Parameter | Description | Type | Required | Default |
| --- | --- | --- | --- | --- |
| id  | Id of the record | [array\_int](/en/help/apidoc/f/ch03.html#type.array_int "3.8. array_int") | true |     |
| name | Name of the nameserver record | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| type | Type of the nameserver record | [recordType](/en/help/apidoc/f/ch03.html#type.recordtype "3.81. recordType") | false |     |
| content | Content of the nameserver record | [text](/en/help/apidoc/f/ch03.html#type.text "3.91. text") | false |     |
| prio | Priority of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| ttl | TTL (time to live) of the nameserver record | [int](/en/help/apidoc/f/ch03.html#type.int "3.56. int") | false |     |
| urlRedirectType | Type of the url redirection | [urlRedirectType](/en/help/apidoc/f/ch03.html#type.urlredirecttype "3.108. urlRedirectType") | false |     |
| urlRedirectTitle | Title of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectDescription | Description of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectFavIcon | FavIcon of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlRedirectKeywords | Keywords of the frame redirection | [token0255](/en/help/apidoc/f/ch03.html#type.token0255 "3.102. token0255") | false |     |
| urlAppend | Append the path for redirection | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |
| testing | Execute command in testing mode | [boolean](/en/help/apidoc/f/ch03.html#type.boolean "3.19. boolean") | false | false |



#### 2.15.13.2. Output

No additional return parameters