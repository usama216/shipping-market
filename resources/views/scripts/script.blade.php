 <script>
     document.addEventListener('DOMContentLoaded', function() {
         const costEstimationBtn = document.querySelector('#costEstimation');
         if (!costEstimationBtn) {
             return; // Element doesn't exist, exit early
         }
         
         costEstimationBtn.addEventListener('click', function() {
             const button = this;
             const container = document.querySelector('.result');
         
         // Get form values
         const country = document.querySelector('#destinationCountry')?.value;
         const weight = document.querySelector('input[name="weight"]')?.value;
         
         // Validate required fields
         if (!country) {
             alert('Please select a destination country');
             return;
         }
         if (!weight || parseFloat(weight) <= 0) {
             alert('Please enter a valid weight');
             return;
         }
         
         // Show loading state
         button.disabled = true;
         button.textContent = 'Calculating...';
         container.innerHTML = '<div style="padding:20px; text-align:center;"><p>Fetching live rates from carriers...</p><p style="font-size:0.9em; color:#666;">This may take a few seconds</p></div>';
         
         const data = {
             length: document.querySelector('input[name="length"]')?.value || 0,
             width: document.querySelector('input[name="width"]')?.value || 0,
             height: document.querySelector('input[name="height"]')?.value || 0,
             dimension_unit: document.querySelector('select[aria-label="Size unit"]')?.value || 'in',
             weight: weight,
             weight_unit: document.querySelector('select[aria-label="Weight unit"]')?.value || 'lb',
             country: country,
         };

         fetch('/calculate-shipping', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                    document.querySelector('meta[name="csrf_token"]')?.content || 
                                    document.querySelector('input[name="_token"]')?.value || ''
                 },
                 body: JSON.stringify(data)
             })
             .then(res => {
                 if (!res.ok) {
                     return res.json().then(err => Promise.reject(err));
                 }
                 return res.json();
             })
             .then(res => {
                 console.log('Calculator response:', res);
                 container.innerHTML = '';

                 // Handle errors
                 if (!res.success) {
                     container.innerHTML = `
                        <div style="padding:20px; background:#ffecec; color:#a94442; border:1px solid #f5c2c2; border-radius:8px;">
                            <strong>Error:</strong> ${res.message || 'Unable to calculate shipping costs'}
                            ${res.errors ? '<br><small>' + JSON.stringify(res.errors) + '</small>' : ''}
                        </div>
                     `;
                     return;
                 }

                 // --- Best Price Card ---
                 if (res.best_price) {
                     container.innerHTML += `
                            <div style="
                    background: #f0fff4;
                    border: 2px solid #38a169;
                    border-radius: 12px;
                    padding: 20px;
                    margin-bottom: 20px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                    font-family: Arial, sans-serif;
                ">
                                <h2 style="color:#2f855a; font-size: 1.3em; margin-bottom: 10px;">🏆 Best Price</h2>
                                <p style="margin: 4px 0;"><strong>Carrier:</strong> ${res.best_price.carrier || 'N/A'}</p>
                                <p style="margin: 4px 0;"><strong>Service:</strong> ${res.best_price.service || 'Standard'}</p>
                                <p style="margin: 4px 0;"><strong>Transit Time:</strong> ${res.best_price.transit_time || 'Varies'}</p>
                                <p style="margin: 4px 0; font-size: 1.4em; font-weight: bold; color:#2f855a;">
                                    ${res.best_price.currency || 'USD'} $${res.best_price.rate || '0.00'}
                                </p>
                                ${res.rate_source === 'live_api' ? '<p style="margin-top:8px; font-size:0.85em; color:#666;">✓ Live rate from carrier API</p>' : ''}
                            </div>
                        `;
                 }

                 // --- Other Estimates Cards ---
                 if (res.best_estimates && res.best_estimates.length) {
                     container.innerHTML +=
                         `<h3 style="font-family: Arial, sans-serif; margin-bottom: 10px; margin-top: 20px;">📦 All Available Options</h3>`;
                     container.innerHTML +=
                         `<div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">`;

                     res.best_estimates.forEach(r => {
                         container.innerHTML += `
                                <div style="
                        background: white;
                        border-radius: 10px;
                        padding: 15px;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                        font-family: Arial, sans-serif;
                        border: 1px solid #eee;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 15px rgba(0,0,0,0.1)'"
                                   onmouseout="this.style.transform='';this.style.boxShadow='0 4px 10px rgba(0,0,0,0.05)'">
                                    <p style="font-weight:bold; font-size: 1.1em;">${r.carrier || 'N/A'}</p>
                                    ${r.service ? `<p style="color: #888; font-size: 0.9em;">${r.service}</p>` : ''}
                                    <p style="color: #555; margin-top: 4px;">Transit: ${r.transit_time || 'Varies'}</p>
                                    <p style="margin-top:8px; font-size: 1.2em; color: #2b6cb0; font-weight: bold;">
                                        ${r.currency || 'USD'} $${r.rate || '0.00'}
                                    </p>
                                </div>
                            `;
                     });

                     container.innerHTML += `</div>`;

                     container.innerHTML += `
                            <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
                                <h3 style="font-size: 1.2em; margin-bottom: 10px;">Marketsz Costs Include:</h3>
                                <ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">
                                    <li>Live shipping rates from major carriers (FedEx, DHL, UPS)</li>
                                    <li>FREE package storage and consolidation</li>
                                    <li>Deep discounts with global carrier partners</li>
                                    <li>Customs documentation completion</li>
                                </ul>
                                <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
                                    <strong>Note:</strong> This amount is an estimate based on the provided dimensions and weight. 
                                    Surcharges may apply due to size, commodity type, and delivery address details and will be 
                                    included in the final shipping charge. Excludes oversized shipments and palletized shipments 
                                    with linear dimensions greater than 72 inches (183 cm).
                                </p>
                                <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                    Rates quoted above are inclusive of any applicable package consolidation and preparation for export.
                                </p>
                            </div>
                        `
                 } else {
                     container.innerHTML += `
                            <div style="padding:20px; background:#fff3cd; color:#856404; border:1px solid #ffeaa7; border-radius:8px;">
                                <strong>No shipping options available</strong><br>
                                <small>Please check your dimensions, weight, and destination country.</small>
                            </div>
                        `;
                 }

                 // --- Note ---
                 if (res.note) {
                     container.innerHTML +=
                         `<p style="margin-top: 15px; font-size: 0.9em; color:#666; font-style: italic;">${res.note}</p>`;
                 }
             })
             .catch(error => {
                 console.error('Calculator error:', error);
                 container.innerHTML = `
                    <div style="padding:20px; background:#ffecec; color:#a94442; border:1px solid #f5c2c2; border-radius:8px;">
                        <strong>Error:</strong> ${error.message || 'Unable to calculate shipping costs. Please try again.'}
                        ${error.errors ? '<br><small>' + JSON.stringify(error.errors) + '</small>' : ''}
                    </div>
                 `;
             })
             .finally(() => {
                 button.disabled = false;
                 button.textContent = 'Get price estimate';
             });
         });
     });
 </script>
<script>
    window.__lc = window.__lc || {};
    window.__lc.license = 12524322;
    window.__lc.integration_name = "manual_channels";
    window.__lc.product_name = "livechat";
    ;(function(n,t,c){function i(n){return e._h?e._h.apply(null,n):e._q.push(n)}var e={_q:[],_h:null,_v:"2.0",on:function(){i(["on",c.call(arguments)])},once:function(){i(["once",c.call(arguments)])},off:function(){i(["off",c.call(arguments)])},get:function(){if(!e._h)throw new Error("[LiveChatWidget] You can't use getters before load.");return i(["get",c.call(arguments)])},call:function(){i(["call",c.call(arguments)])},init:function(){var n=t.createElement("script");n.async=!0,n.type="text/javascript",n.src="https://cdn.livechatinc.com/tracking.js",t.head.appendChild(n)}};!n.__lc.asyncInit&&e.init(),n.LiveChatWidget=n.LiveChatWidget||e}(window,document,[].slice))
</script>
<noscript><a href="https://www.livechat.com/chat-with/12524322/" rel="nofollow">Chat with us</a>, powered by <a href="https://www.livechat.com/?welcome" rel="noopener nofollow" target="_blank">LiveChat</a></noscript>
