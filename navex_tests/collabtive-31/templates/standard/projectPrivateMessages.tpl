<!-- container for the messagesContent accordeon -->
<div class="msgs" id="privateMessages" v-cloak>
    <div class="headline">
        <!-- toggle for the blockaccordeon-->
        <a href="javascript:void(0);" id="privateMessages_toggle" class="win_none" onclick=""></a>

        <div class="wintools">
            <loader block="userMessages" loader="loader-messages.gif"></loader>
        </div>

        <h2>
            <img src="./templates/{$settings.template}/theme/{$settings.theme}/images/symbols/msgs.png"
                 alt=""/>{#personalmessages#}
            <pagination view="userMessagesView" :pages="pages" :current-page="currentPage"></pagination>
        </h2>
    </div>

    <!-- contentSlide for the blockAccordeon -->
    <div id="block_privateMessages" class="block blockaccordion_content overflow-hidden display-none">
        <div class="nosmooth" id="sm_msgs_private">
            <table id="tablePrivateMessages" cellpadding="0" cellspacing="0" border="0">
                <thead>
                <tr>
                    <th class="a"></th>
                    <th class="b">{#message#}</th>
                    <th class="ce" class="text-align-right">{#replies#}&nbsp;&nbsp;</th>
                    <th class="de">{#by#}</th>
                    <th class="e">{#on#}</th>
                    <th class="tools"></th>
                </tr>
                </thead>

                <tfoot>
                <tr>
                    <td colspan="6"></td>
                </tr>
                </tfoot>
                {literal}
                <tbody v-for="message in items.private" class="alternateColors"
                       v-bind:id="'privMsgs_'+message.ID">
                <tr>
                    <td>
                        {/literal}
                        {if $userpermissions.messages.close}
                        {literal}
                            <a class="butn_reply" href="javascript:void(0);" onclick="change('managemessage.php?action=replyform&amp;mid={{*message.ID}}&amp;id={{*message.project}}','addmsg');toggleClass(this,'butn_reply_active','butn_reply');blindtoggle('addmsg');" title="{/literal}{#edit#}"></a>
                        {/if}
                    </td>
                    {literal}
                    <td>
                        <div class="toggle-in">
                            <!--toggle for the messagesContent accordeon-->
                                    <span class="acc-toggle"
                                          onclick="javascript:accord_user_messages.toggle(css('#privateMessages_content{{$index}}'));"></span>
                            <a v-bind:href="'managemessage.php?action=showmessage&amp;mid='+message.ID+'&amp;id='+message.project"
                               :title="message.title">{{{*message.title | truncate '30' }}}</a>
                        </div>
                    </td>
                    <td class="text-align-right">
                        <a v-bind:href="'managemessage.php?action=showmessage&amp;mid='+message.ID+'&amp;id='+message.project+'#replies'">
                            {{message.replies}}</a>
                        &nbsp;
                    </td>
                    <td><a v-bind:href="'manageuser.php?action=profile&amp;id='+message.user">{{message.username}}</a>
                    </td>
                    <td>{{message.postdate}}</td>
                    <td class="tools">
                        {/literal}
                        {if $userpermissions.messages.edit}
                        {literal}
                            <a class="tool_edit" href="javascript:void(0);" onclick="change('managemessage.php?action=editform&amp;mid={{*message.ID}}&amp;id={{*message.project}}','addmsg');toggleClass(this,'tool_edit_active','tool_edit');blindtoggle('addmsg');" title="{/literal}{#edit#}"></a>
                        {/if}
                        {if $userpermissions.messages.del}
                        {literal}
                            <a class="tool_del" href="javascript:confirmDelete('{/literal}{#confirmdel#}{literal}','msgs_{{*message.ID}}','managemessage.php?action=del&amp;mid={{*message.ID}}&amp;id={{*message.project}}',projectMessagesView);"  title="{/literal}{#delete#}"></a>
                        {/if}
                        {literal}
                    </td>
                </tr>

                <tr class="acc">
                    <td colspan="6">
                        <!--contentSlide for the messagesContent accordeon-->
                        <div class="accordion_content">
                            <div class="message-fluid">
                                <div class="message-in-fluid">
                                    <div class="avatar">
                                        <template v-if="message.avatar != ''">
                                            <img v-bind:src="'thumb.php?width=80&amp;height=80&amp;pic=files/standard/avatar/' +message.avatar"
                                                 alt=""/>
                                        </template>
                                        <template v-else>
                                            <img src="thumb.php?width=80&amp;height=80&amp;pic=templates/{/literal}{$settings.template}/theme/{$settings.theme}{literal}/images/no-avatar-male.jpg"/>
                                        </template>
                                    </div>
                                    {{{message.text}}}
                                </div>

                                <!-- message milestones -->
                                <template v-if="message.hasMilestones">
                                    <div class="content-spacer-b"></div>
                                    <h2>{/literal}{#milestones#}{literal}</h2>

                                    <div class="dtree"
                                         :id="'milestoneTree' + message.ID">

                                    </div>
                                </template>
                                <!-- message files -->
                                <template v-if="message.hasFiles">
                                    <div class="content-spacer-b"></div>
                                    <h2>{/literal}{#files#}{literal}</h2>

                                    <div class="dtree"
                                         :id="'filesTree' + message.ID">

                                    </div>
                                </template>
                            </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <!--smooth End-->
        {/literal}
    </div>
    <!-- block END  -->
    <div class="padding-bottom-two-px"></div>
</div><!--msgs end-->