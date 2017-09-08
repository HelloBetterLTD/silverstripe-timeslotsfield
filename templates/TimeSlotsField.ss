<div class="ss-time-slots-holder js-time-slots-holder" id="$ID">
    <div class="ss-time-slots template-download js-time-slots" data-name="$Name">

        <% if $Items %>
            <% loop $Items %>
                <div class="ss-time-slots-wrapper js-time-row" data-id="$ID" data-name="$Up.Name">
                    <div class="ss-time-slots-item time">
                        $TimeField($Up.Name, $Status).SmallFieldHolder
                    </div>
                    <div class="ss-time-slots-item" >
                        <button class="ss-time-slots-button add js-time-add add">+</button>
                        <button class="ss-time-slots-button remove js-time-remove delete js-can-delete">-</button>
                    </div>
                </div>
            <% end_loop %>
        <% else %>
            <div class="ss-time-slots-wrapper js-time-row" data-name="$Name">
                <div class="ss-time-slots-item time">
                    $TimeField.SmallFieldHolder
                </div>
                <div class="ss-time-slots-item">
                    <button class="ss-time-slots-button add js-time-add add">+</button>
                    <button class="ss-time-slots-button remove js-time-remove delete">-</button>
                </div>
            </div>
        <% end_if %>

        <% if $ItemsToBeDeleted %>
            <% loop $ItemsToBeDeleted %>
                $Me
            <% end_loop %>
        <% end_if %>

    </div>
</div>