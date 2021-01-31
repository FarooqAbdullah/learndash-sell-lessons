# LearnDash Sell Lessons
It allows admin to sell LearnDash lessons along with the courses. 

### Notes
- This add-on requires WooCommerce and LearnDash to be installed & configured in order to perform the functions.
- Since, it's a custom add-on made for a specific project therefore, use it on your own.

### Detail
Once you have configured WooCommerce and LearnDash and activated this add-on, you will see a new menu under LearDash section named "Sell LearnDash Lessons". There are two options here for admin. 
1) Admin can add/update the restriction message that user will see on lesson detail page if the lesson is not purchased.
2) Additional percentage for lesson price
 
Lesson Price: 
The formula to calculate the lesson price is as below. 
There is an option on lesson edit page to add the price for the lesson if admin fills it then the price of lesson would be "price added on lesson edit page" + "percentage of course price as indicated on setting page". While if user doesn't add the price for lesson on edit page then course price would be divided among the lessons e.g if there are 5 lessons in a course and course price is $100 then one lesson price would be $20 + "percentage of course price as indicated on setting page".

### Creating Course Product
After activating the add-on admin will create course product in WooCommerce. The add-on adds a new product type "LearnDash Courses", on selecting this prodct type new additional option appears to select the course attach with the product.

On front-end, while purchasing product user will see all the lessons (assigned to the attached course) listed and can select one or more lessons to purchase. The price of selected lessons will be updated automatically to the cart/checkout pages. User will also see the button to purchase the lesson on course detail page. On clicking that button user will be redirected to the checkout page having that lesson added to the cart automatically.
