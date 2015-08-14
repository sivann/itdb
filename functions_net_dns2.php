<?php
/********************************* NET_DNS2 RELATED QUERIES IN FUNCTION FORM ****************************************/

require_once 'NET/DNS2.php';

/* Return DNS suffix with dot */
function suffix_dot($suffix)
{
    //Make sure hostname is not empty
    if($suffix != "")
    {
        $correct_suffixes="";
        //Explode DNS suffixes
        $suffix_array = explode(',', $suffix);

        // Loop thru array to make sure every suffix has a leading DOT
        foreach ($suffix_array as $ss)
        {
            //Trim whitespace before suffix
            $ss=ltrim($ss);
            //Check if suffix has leading DOT
            $suffix_dot = substr_compare($ss, ".", 0, 1);
            //Add DOT when necessary
            if ($suffix_dot != 0 )
            {
                $correct_suffixes .= ".".$ss.",";
            }
            else
            {
                $correct_suffixes .= $ss.",";
            }
        }
        //Trim whitespaces and unnecessary comma at the end
        $correct_suffixes=trim($correct_suffixes);
        $correct_suffixes=rtrim($correct_suffixes, ",");

        return $correct_suffixes;
    }
}

/* Return IPv4 addresses for hostname of item */
function getipv4fromdns($hostname)
{
    //Make sure hostname is not empty
    if($hostname != "")
    {
        //Get DNS Servers
        $dns_servers = dns_servers();

        //Get DNS suffixes
        $suffix = dns_suffix();

        //Explode DNS suffixes
        $suffix_array = explode(',', $suffix);

        //Build array for hostnames with every suffix and without suffix
        foreach ($suffix_array as $suffix)
        {
            $hostname_array .= $hostname.$suffix.",";
        }
        $hostname_array .= $hostname;

        //Explode DNS Servers
        $dns_servers_array = explode(',', $dns_servers);

        //NET DNS2 Resolver with 1 second timeout (default = 5)
        $r = new Net_DNS2_Resolver(array('nameservers' => $dns_servers_array, 'timeout' => (1)));

        //Loop thru array to make sure every hostname+suffix combination from the array is checked against every DNS Server until (hopefully) a valid response was received
        $success=false;
        $hostname_array = explode(',', $hostname_array);
        foreach ($hostname_array as $hostname)
        {
            //Try DNS query and catch exceptions from NET_DNS2
            $ipv4 = "";
            try {
                $result = $r->query($hostname);
                $success=true;
                break;
            }
            catch (Net_DNS2_Exception $e) {
                $ipv4[0] = "DNS Verification failed! Error: ".$e->getMessage();
            }
        }
        // Loop thru result array to get IP Addresses from result when DNS query succeded
        if($success)
        {
            foreach ($result->answer as $rr)
            {
                if (($rr->type == 'A') && ($r->isIPv4($rr->address)))
                {
                    $ipv4 = $rr->address.",".$ipv4;
                }
            }

            //Trim whitespaces and commas
            $ipv4 = trim($ipv4);
            $ipv4 = rtrim($ipv4,",");

            //Sort IP Addresses
            $ipv4_array = explode(',', $ipv4);
            natsort($ipv4_array);
            $ipv4 = implode(', ', $ipv4_array);

            //Trim whitespaces and commas again
            $ipv4 = trim($ipv4);
            $ipv4 = rtrim($ipv4,",");
        }
    }
    else
    {
        $ipv4[0] = "Error! Hostname is empty, unreadable or unresolveable!";
        //Give back an error due to hostname error
    }

    return $ipv4;
}
