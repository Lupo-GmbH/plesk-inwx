# Third-Party DNS Services

By default, a Plesk server acts as a primary DNS server for websites hosted on it. Alternatively, you can use external third-party DNS services to resolve domain names hosted in Plesk, for example, _Amazon Route 53_ ([http://aws.amazon.com/route53/](http://aws.amazon.com/route53/)), _DynECT_ ([http://dyn.com/dns/dynect-managed-dns/](http://dyn.com/dns/dynect-managed-dns/)), or _Godaddy Premium DNS_ ([http://www.godaddy.com/domains/dns-hosting.aspx](http://www.godaddy.com/domains/dns-hosting.aspx)). Using such services has certain advantages:

*   Reduced load on your Plesk server.
*   Improved DNS hosting reliability.

If you plan to use an external third-party DNS service, you can configure your Plesk so that it uses this service instead of its own DNS server. This will not affect website owners: From their perspective, Plesk stays a primary DNS server for their domains.

To make your Plesk automatically provision DNS zones to a third-party DNS, you should write an integration script.

## DNS Integration Script

The integration script is a script (written in any language supported by your server) that propagates changes made to DNS zones in Plesk UI to the selected third-party DNS service. The script should communicate with the external service by means of the service’s API.

After you register the script in Plesk, the latter will pass to the script information about any change in DNS zones, for example, adding or removing resource records or entire zones.

![image 76177.gif](https://docs.plesk.com/en-US/obsidian/extensions-guide/images/76177.gif)

The script must meet the following requirements:

*   It should be able to process the input parameters given in the table below.
*   If the actions are completed successfully, the script should return `errorcode0`.
*   If you want your script to write information about its execution to Plesk log files, send this information to the script’s `stdout` and `sterr`.

An example of the script that integrates Plesk with the _Amazon Route 53_ is available on [our GitHub page](https://github.com/plesk/ext-route53).

Note that to use this script, you should take two steps:

*   Download the library for working with Amazon Web Services in PHP - `aws.phar` from [http://aws.amazon.com/sdkforphp/](http://aws.amazon.com/sdkforphp/) and place it in the same directory with the script.
*   Specify your Amazon security credentials in the script (lines 23 and 24):

'client' \=> array(
    'key' \=> '<key>',
    'secret' \=> '<secret>',
),

# Integration Script Input Parameters

**Note:** All parameters are of the `string` type.

| Parameter |     |     | Description | Example |
| --- | --- | --- | --- | --- |
| command |     |     | The operation that should be performed on the specified zone (`create`, `update`, or `delete`) or reverse (PTR) record `(createPTRs` or `deletePTRs)` | `create` |
| `zone` |     |     | Information about the zone on which the operation is performed. Provided if the `command` is `create`, `update`, or `delete` |     |
|     | `name` |     | The name of the zone. | `d10.tld.` |
|     | `displayName` |     | The name of the zone without IDN to ASCII conversion. | `d10.tld.` |
|     | `soa` |     | Information about the SOA resource record in the zone. Provided if the `command` is `create` or `update`. |     |
|     |     | `email` | Contact email | `domainowner@samplemail.com` |
|     |     | `status` | Always `0`. | `0` |
|     |     | `type` | Type of the DNS server. Always `master`. | `master` |
|     |     | `ttl` | The amount of time in seconds that other DNS servers should store the record in a cache. | `86400` |
|     |     | `refresh` | Time in seconds how often the secondary name servers check with the primary name server to see if any changes have been made to the domain’s zone file. | `10800` |
|     |     | `retry` | Time in seconds a secondary server waits before retrying a failed zone transfer. | `3600` |
|     |     | `expire` | Time in seconds before a secondary server stops responding to queries, after a lapsed refresh interval where the zone was not refreshed or updated. | `604800` |
|     |     | `minimum` | Time in seconds a secondary server should cache a negative response | `10800` |
|     |     | `serial` | The number that identifies the state of the zone. Plesk uses the Unix time stamp of the moment when the request is sent. | `1363228965` |
|     |     | `serial_format` | Always `"UNIXTIMESTAMP"` |     |
|     |     | `rr` |     | Information about resource records within the specified zone. Provided if the `command` is `create` or `update`. |
|     | `host` |     | The host name of a node within the zone. | `www.d10.tld.` |
|     | `displayHost` |     | The host name of a node without IDN to ASCII conversion. | `www.d10.tld.` |
|     | `type` |     | The type of the record. | `CNAME` |
|     | `opt` |     | The part of the `value` without IP or hostname. |     |
|     | `value` |     | A value (IP address of host name) of the resource that will be available on the specified `host`. | `d10.tld.` |
|     | `displayValue` |     | A value (IP address of host name) of the resource that will be available on the specified `host` without IDN to ASCII conversion. | `d10.tld.` |
| `ptr` |     |     | Information about the reverse (PTR) records. Provided if the `command` is `createPTRs` or `deletePTRs`. |     |
|     | `ip_address` |     | The reverse record’s IP address. May be IPv4 or IPv6 address. | `10.52.59.29` |
|     | `hostname` |     | The reverse record’s host name. | `d10.tld` |

# Integrating Plesk with a Third-Party DNS

To switch on Plesk integration with third-party DNS

1.  Put your integration script into your extension’s `/plib/scripts/` directory
    
    For example: `/plib/scripts/route53.php`
    
2.  Make sure that your script can be executed successfully from command line
    
    On Linux: `/usr/local/psa/bin/extension --exec <extension_id> <script_name>`
    
    On Windows: `%plesk_dir%bin\extension.exe --exec <extension_id> <script_name>`
    
    For example: `/usr/local/psa/bin/extension --exec route53 route53.php`
    
3.  Set up the `post-install.php` script to register custom DNS backend
    
    On Linux: `plesk bin server_dns --enable-custom-backend <script_execution>`
    
    On Windows: `%plesk_dir%bin\server_dns.exe --enable-custom-backend <script_execution>`
    
    For example:
    
    $script \= '/usr/local/psa/bin/extension --exec route53 route53.php';
    $result \= pm\_ApiCli::call('server\_dns', \['--enable-custom-backend', $script\]);
    

To switch off Plesk integration with third-party DNS and restore the default settings

Set up the `pre-uninstall.php` script to disable custom DNS backend

On Linux: `plesk bin server_dns --disable-custom-backend`

On Windows: `%plesk_dir%bin\server_dns.exe --disable-custom-backend`

For example: `$result = pm_ApiCli::call('server_dns', ['--disable-custom-backend']);`

You can find a complete example of the integration on github: [https://github.com/plesk/ext-route53](https://github.com/plesk/ext-route53)

## Remarks

# Verifying Successful DNS Zones’ Synchronization

Following the integration of Plesk DNS with an external DNS service, domains’ DNS zone records are synchronized with that service. The successful synchronization of a certain domain’s DNS zone adheres to the following:

1.  The DNS zone for this domain is created at the external service.
2.  All the domain’s DNS records are correctly copied to the external service.
3.  In the extension UI, the domain is marked as synchronized with the external service.
4.  The user receives a message that the DNS zone for the specified domain is now successfully synchronized with the external service.
5.  If a certain DNS record is not supported by the external service, the user receives a message in the extension UI that this record is not supported by the external service.
6.  If the entire DNS zone cannot be synchronized, the user receives an appropriate message in the extension UI.

Synchronization Specifics Related to Different Supported Domain Types

Plesk supports a wide [variety of different types of domains](https://docs.plesk.com//en-US/obsidian/administrator-guide/72051/ "(in Administrator's Guide vobsidian)"). Some of these pose additional important considerations.

**Note:** By default, DNS zones for all domain types are _Enabled_ and _Primary_, except for the subdomains. The subdomains use its parent domain’s DNS zone. Individual DNS zone for a subdomain can be enabled in the **DNS Settings** for that subdomain.

| Domain type | Specific issues |
| --- | --- |
| Subscription | n/a |
| Addon domain | n/a |
| Subdomain | Synchronization is only possible for the subdomains with own DNS zone.<br><br>Subdomains with parent DNS zones cannot by synchronized. |
| Domain alias | [Domain alias DNS zone may or may not be synchronized with the primary domain DNS zone.](https://docs.plesk.com//en-US/obsidian/administrator-guide/65286/ "(in Administrator's Guide vobsidian)") The result of synchronizing the domain alias DNS zone with an external service must not depend on it. |
| No hosting domain | n/a |
| Forwarding | n/a |
| Domain on IPv6 | Add an IPv6 IP address (if there are none) at **Tools & Settings > IP Addresses**, and use it to create a subscription, to verify synchronization for such a domain. |
| IDN domain | Make sure, the domain name is displayed in national encoding in the extension UI. |

Domains Unavailable for Synchronization

Due to Plesk’s policies, the following domains’ DNS zones cannot be synchronized to an external service:

*   domains with Disabled DNS zone
*   domains with Secondary DNS zone
*   disabled domains
*   suspended domains

Observing Service Limits

If the external DNS service enforces certain resource limits, the extension must not allow the user to execute a resource-consuming action when the corresponding limit is reached. An appropriate message must be displayed to the user in the extension UI.

# Verifying Operations with Synchronized DNS Zones

The basic idea is to make sure that all the changes to the DNS settings done in Plesk are correctly propagated to the external service.

Choose a domain with its DNS zone synchronized with an external DNS service, and check the following.

Operations with DNS Zone

1.  Switching DNS Zone from _Primary_ to _Secondary_ prevents synchronization. If a domain DNS zone has already been synchronized with the external service, switching it from _Primary_ to _Secondary_ changes nothing, the DNS zone records remain on the external service untouched. If a domain DNS zone has not yet been synchronized, once switched to _Secondary_, it must be impossible to synchronize it.
2.  Applying _DNS Template_ must be correctly reflected. When a user applies _DNS Template_ for an active DNS zone, DNS records must be correspondingly updated on the external service.
3.  Resetting DNS settings must be correctly reflected. When a user resets DNS settings to defaults for an active DNS zone, its DNS records must be correspondingly updated on the external service.

Operations with DNS Records

1.  Adding any type of DNS record in Plesk adds the record on the external service. Must work for all types of DNS records.
2.  Editing a DNS record in Plesk changes the corresponding record on the external service.
3.  Removing a DNS record in Plesk removes the corresponding record on the external service.

Operations with Domain

Make sure that changes to the domain’s hosting settings are reflected appropriately in the external DNS records.

1.  Changing the domain name. When the name of the domain is changed, you can choose to either update the external service DNS records accordingly or to erase them completely. This change must be appropriately reflected in the Plesk extension UI.
    
2.  Changing the domain’s IP address in Plesk causes updating the IP address in the external service DNS records.
    
3.  Disabling a synchronized domain does NOT cause its DNS records to be erased from the external service.
    
    **Note:** If a domain is disabled before synchronizing with the external DNS service, it cannot be synchronized at all.
    
4.  Suspending a synchronized domain does NOT cause its DNS records to be erased from the external service.
    
    **Note:** If a domain is suspended before synchronizing with the external DNS service, it cannot be synchronized at all.
    
5.  Removing a domain in Plesk must cause all its DNS records to be removed from the external service.