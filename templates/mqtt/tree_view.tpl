<style type="text/css">
    .HandCursorStyle {
        cursor: pointer;
        cursor: hand;
    }
</style>

<script type="text/JavaScript">
    // Add this to the onload event of the BODY element
    function addEvents() {
        activateTree(document.getElementById("LinkedList1"));
    }

    // This function traverses the list and add links
    // to nested list items
    function activateTree(oList) {
        // Collapse the tree
        /*
      for (var i=0; i < oList.getElementsByTagName("ul").length; i++) {
        oList.getElementsByTagName("ul")[i].style.display="none";
      }
                                                                     */
        // Add the click-event handler to the list items
        if (oList.addEventListener) {
            oList.addEventListener("click", toggleBranch, false);
        } else if (oList.attachEvent) { // For IE
            oList.attachEvent("onclick", toggleBranch);
        }
        // Make the nested items look like links
        addLinksToBranches(oList);
    }

    function rememberBranchStatus(title, status) {
        var url="?ajax=1&op=branch_status&status="+status+"&branch="+encodeURIComponent(title);
        $.ajax({
            url: url
        }).done(function(data) {
            //alert(data);
        });
    }

    // This is the click-event handler
    function toggleBranch(event) {
        var oBranch, cSubBranches;
        if (event.target) {
            oBranch = event.target;
        } else if (event.srcElement) { // For IE
            oBranch = event.srcElement;
        }
        cSubBranches = oBranch.getElementsByTagName("ul");
        device_titles = oBranch.getElementsByClassName("device_title");
        if (cSubBranches.length > 0) {
            if (cSubBranches[0].style.display != "none") {
                $(cSubBranches[0]).hide('slow');
                rememberBranchStatus(oBranch.title,0);
            } else {
                $(cSubBranches[0]).show('slow');
                rememberBranchStatus(oBranch.title,1);
            }
        }
        if (device_titles.length > 0) {
            if (device_titles[0].style.display == "none") {
                $(device_titles[0]).show('fast');
            } else {
                $(device_titles[0]).hide('fast');
            }
        }
    }

    // This function makes nested list items look like links
    function addLinksToBranches(oList) {
        var cBranches = oList.getElementsByTagName("li");
        var i, n, cSubBranches;
        if (cBranches.length > 0) {
            for (i=0, n = cBranches.length; i < n; i++) {
                cSubBranches = cBranches[i].getElementsByTagName("ul");
                if (cSubBranches.length > 0) {
                    addLinksToBranches(cSubBranches[0]);
                    cBranches[i].className = "HandCursorStyle";
                    //cBranches[i].style.fontWeight="bold";
                    cSubBranches[0].style.color = "black";
                    cSubBranches[0].style.fontWeight = "normal";
                    cSubBranches[0].style.cursor = "auto";
                }
            }
        }
    }
</script>

{if $RESULT}
<ul id="LinkedList1" class="LinkedList" style="padding-left: 0px;">
{function name=menu}
{foreach $items as $item}
    <li style="list-style-type: none; color:{$item.COLOR}" title="{$item.TITLE}" onmouseout="$(this).find('.delIcon_{$item.ID}').hide().removeClass('text-danger');" onmouseover="$(this).find('.delIcon_{$item.ID}').show().addClass('text-danger');">
        {if $item.RESULT}
        <i class="glyphicon glyphicon-folder-close" style="font-size: 1.2rem;"></i>
        {else}
        <i class="glyphicon glyphicon-arrow-right" style="font-size: 1.2rem;color: #ddd;"></i>
        {/if}
        {if $item.ID!=""}
        <a href="?view_mode=edit_mqtt&id={$item.ID}" title="{$item.PATH}" style="color:{$item.COLOR};text-decoration: none;">
         {if $item.TITLE!=""}{$item.TITLE}{else}[..]{/if}
        </a>
        : <span id="mqtt{$item.ID}" class="mqtt_value">{$item.VALUE}</span>
        {if $item.LINKED_OBJECT!=""}<i>
        ({if $item.LINKED_PROPERTY==""}M: {$item.LINKED_OBJECT}.{$item.LINKED_METHOD}{else}P: {$item.LINKED_OBJECT}.{$item.LINKED_PROPERTY}{/if})
        {/if}
        </i>{else}
        &nbsp;{$item.TITLE}
        {/if}
        <span class="device_title" {if $item.IS_VISIBLE=="1"} style="display:none"{/if}>{if $item.DEVICE_TITLE!=""}<span>&mdash; <i><a href="?(panel:{literal}{action=devices}{/literal})&md=devices&view_mode=edit_devices&id={$item.DEVICE_ID}&tab=settings">{$item.DEVICE_TITLE}</a></i></span>{/if}</span>

        <a href="?view_mode=delete_path&path={$item.PATH_URL}" onclick="return confirm('{$smarty.const.LANG_ARE_YOU_SURE}');"><i style="display: none;" class="glyphicon glyphicon-remove delIcon_{$item.ID}"></i></a>
        {if $item.RESULT}
        <ul {if $item.IS_VISIBLE!="1"} style="display:none"{/if}>
            {menu items=$item.RESULT}
        </ul>
        {/if}

    </li>

{/foreach}

{/function}
{menu items=$RESULT}

</ul>
{/if}

<script type="text/JavaScript">
    addEvents();
</script>
