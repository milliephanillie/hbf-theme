<div id="selectCustomerModal" class="custom-modal" style="display:none;">
    <button id="closeSelectCustomerModal" class="close-button">&times;</button>
    <h2>Select Customer</h2>
    <form id="selectCustomerForm" class="custom-form">
        <label for="customerSearch">Search Customer:</label>
        <input type="text" id="customerSearch" name="customerSearch">
        <button type="submit">Search</button>
    </form>
</div>

<div id="createCustomerModal" class="custom-modal" style="display:none;">
    <button id="closeCreateCustomerModal" class="close-button">&times;</button>
    <h2>Create Customer</h2>
    <form id="createCustomerForm" class="create-customer-form">
        <!-- Billing Information -->
        <label for="billing_first_name">First Name:</label>
        <input type="text" id="billing_first_name" name="billing_first_name">

        <label for="billing_last_name">Last Name:</label>
        <input type="text" id="billing_last_name" name="billing_last_name">

        <label for="billing_company">Company:</label>
        <input type="text" id="billing_company" name="billing_company">

        <label for="billing_address_1">Address 1:</label>
        <input type="text" id="billing_address_1" name="billing_address_1">

        <label for="billing_address_2">Address 2:</label>
        <input type="text" id="billing_address_2" name="billing_address_2">

        <label for="billing_city">City:</label>
        <input type="text" id="billing_city" name="billing_city">

        <label for="billing_state">State:</label>
        <select id="billing_state" name="billing_state">
            <!-- States will be populated here -->
        </select>

        <label for="billing_postcode">Postcode:</label>
        <input type="text" id="billing_postcode" name="billing_postcode">

        <label for="billing_country">Country:</label>
        <select id="billing_country" name="billing_country">
            <!-- Populate this dropdown with countries -->
        </select>

        <label for="billing_phone">Phone:</label>
        <input type="text" id="billing_phone" name="billing_phone">

        <label for="billing_email">Email:</label>
        <input type="email" id="billing_email" name="billing_email">

        <label for="account_password">Password:</label>
        <input type="password" id="account_password" name="account_password">

        <label for="account_password_confirm">Confirm Password:</label>
        <input type="password" id="account_password_confirm" name="account_password_confirm">

        <label><input type="checkbox" id="sendLoginLink" name="sendLoginLink"> Send Login Link via Email</label>

        <label><input type="checkbox" id="isDistributor" name="isDistributor"> Assign Distributor Role</label>

        <label><input type="checkbox" id="isExport" name="isExport"> Assign Export Role</label>

        <label><input type="checkbox" id="isInternational" name="isInternational"> Assign International Role</label>

        <div id="creditCardFields" style="display:none;">
            <label for="customerCardNumber">Card Number:</label>
            <input type="text" id="customerCardNumber" name="customerCardNumber">
            <label for="customerCardExpiry">Card Expiry:</label>
            <input type="text" id="customerCardExpiry" name="customerCardExpiry">
            <label for="customerCardCVC">CVC:</label>
            <input type="text" id="customerCardCVC" name="customerCardCVC">
        </div>

        <button type="submit">Create</button>
    </form>

</div>

