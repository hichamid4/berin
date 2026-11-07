# BreIn Functional Specification

**Status:** Draft for review — no application implementation authorized by this document  
**Source of functional requirements:** *Mémoire de Projet de Fin de Cycle — Web Application: BreIn*, Hicham ID AHMED, academic year 2022–2023 (43 pages).  
**Purpose:** Consolidate the original report into a traceable specification. Report statements, interpretations, and technical proposals are labeled separately.

## 1. Reading guide and source limits

- **[Report]** means stated in report prose, shown in a figure, or both.
- **[Proposal]** means a technical organization suggested here; it is not an original requirement.
- **[Open]** means the report is contradictory, incomplete, or does not establish whether a feature is intended for the first release.

The report includes prose, a requirements/module diagram, a use-case diagram, a class diagram, two sequence diagrams, and screenshots/descriptions of some existing pages. It does **not** include database DDL, field-level validation rules, API contracts, or complete textual use-case specifications. Cardinalities and attributes below are transcribed from the class diagram where legible; their implementation meaning must be confirmed where unclear.

## 2. Product summary

**[Report]** BreIn is a responsive web application for connecting homeowners seeking home-improvement/repair services with handymen. Handymen state their skills; homeowners discover services and create requests; handymen submit offers; homeowners accept or reject offers; direct communication follows acceptance. The report names homeowners, handymen, and administrators as actors.

**[Report]** The documented UI includes login, account signup, services, request creation, a homeowner dashboard, About Us, and Contact Us. The report names Laravel, PHP, Bootstrap, HTML, CSS, and JavaScript as the original implementation stack. These historical choices do not mandate the rebuilt stack.

## 3. Functional requirements

IDs below are assigned for traceability; they are not identifiers from the report.

### 3.1 Accounts and access

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| ACC-01 | A user can register as either a Homeowner or Handyman. | Brief, signup page description, use-case diagram | Explicit |
| ACC-02 | Signup collects user information: first name, last name, login/username, password, email, phone, city, address, and account type. | Account-module workflow | Explicit; exact field names/requiredness unclear |
| ACC-03 | Handyman signup/profile specifies skills. | Brief; class diagram Skills | Explicit |
| ACC-04 | Validate submitted registration data; save if valid and return to signup if invalid. | Account-module workflow | Explicit; validation rules unspecified |
| ACC-05 | Confirm account creation and send an email notification. | Account-module workflow | Explicit |
| ACC-06 | Activate an account through a link/button in an email; on success update account state and redirect to BreIn home; on failure return to login. | Account-module workflow | Explicit |
| ACC-07 | Users can log in with email and password. | Login page description | Explicit |
| ACC-08 | Users can log out. | User class; screenshot description | Explicit |
| ACC-09 | A user can edit account/profile information. | Account module; homeowner dashboard | Explicit |
| ACC-10 | A user can change/update their password. | Account module | Explicit |
| ACC-11 | A user can delete their account. | Account module | Explicit in module; flow/details unspecified |
| ACC-12 | The user diagram includes “Forgot Password.” | Use-case diagram | Explicit in diagram; workflow unspecified |
| ACC-13 | “Activate account” is labeled “2FA” in the module/organigram. | Account module and organigram | Open: conflicts with email activation flow; does not define second-factor authentication |
| ACC-14 | Administrator authenticates to a protected admin account/area. | Admin actor description and diagram; project brief supplied by user | Explicit at role level; exact login flow unspecified |

### 3.2 Catalog and discovery

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| CAT-01 | Homeowner can browse/consult the service catalog and category list. | Brief, use-case diagram, class diagram | Explicit |
| CAT-02 | Homeowner can search services by category. | Brief, use-case diagram, services page description | Explicit |
| CAT-03 | Homeowner can search by service and price. | Use-case diagram | Explicit in diagram; price semantics/range unspecified |
| CAT-04 | Homeowner can view/list handymen. | Use-case diagram and organigram | Explicit in diagram |
| CAT-05 | Homeowner can search handymen by rating, location, or likes. | Use-case diagram | Explicit in diagram; meaning and ranking unspecified |
| CAT-06 | Handyman can search/filter requests by category, service, or location. | Use-case diagram; actor prose says filtering is future work | Open: diagram shows search; prose explicitly describes filtering as future functionality |
| CAT-07 | Handyman sees only requests matching their skills. | Project brief | Explicit |

### 3.3 Requests and offers

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| REQ-01 | Homeowner creates a service request after selecting a category/service. | Brief, request sequence, page descriptions | Explicit |
| REQ-02 | Request contains service description and desired work start date; location is mentioned in homeowner use-case prose. | Request page; homeowner use-case prose | Explicit; exact location fields unspecified |
| REQ-03 | Request can include one or more photos; photos are optional. | Request page, sequence, class diagram PictureOff | Explicit |
| REQ-04 | Request sequence: list categories → list services for category → choose service → create request → optionally add pictures. | Sequence diagram | Explicit |
| REQ-05 | Handyman views/list requests and request details. | Handyman use-case diagram and offer sequence | Explicit |
| REQ-06 | Handyman submits an offer in response to a request. | Brief, class diagram, offer sequence | Explicit |
| REQ-07 | Offer contains price, start date, end date, and description. | Class diagram Offer | Explicit as modeled; requiredness/meaning of dates unclear |
| REQ-08 | Offer may include pictures. | Offer sequence and PictureOff class | Explicit in diagram |
| REQ-09 | Homeowner can review received offers and accept or refuse an offer. | Brief and homeowner actor prose | Explicit |
| REQ-10 | The system supports offer/request status sufficient to show whether an offer has been sent and whether it was accepted/refused. | Homeowner dashboard description | Explicit outcome; state model unspecified |
| REQ-11 | Handyman can create a request for a service they offer. | Handyman actor prose | Open: conflicts with homeowner-only request flow and sequence diagram |
| REQ-12 | Handyman can accept or decline requests. | Handyman prose and use-case diagram | Open: conflicts with core offer workflow; relationship to submitting an offer unspecified |
| REQ-13 | Handyman can display/manage their request list and accept/decline items. | Use-case diagram | Explicit in diagram; semantics and relationship to REQ-11/12 open |

### 3.4 Communication, appointments, reviews, and payment

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| COM-01 | After a homeowner accepts/reserves a service, the parties can communicate directly. | Brief | Explicit; channel is not fixed here |
| COM-02 | Homeowner can contact handyman via phone number. | Dashboard description | Explicit |
| COM-03 | Homeowner use-case says contact is via WhatsApp after booking. | Actor description | Explicit mention; whether this means a deep link, stored number, or in-app integration is unspecified |
| COM-04 | Use-case/organigram includes messaging, instant messages, sharing pictures/documents, and scheduling appointments. | Use-case diagram and organigram | Explicit in figures; delivery and scope unclear |
| COM-05 | Homeowner and handyman can schedule, accept, decline, and reschedule appointments. | Use-case diagram and class methods | Explicit in figures; rules/time-zone/status details unspecified |
| REV-01 | Homeowner can review/rate a handyman. | Brief, use-case diagram, class diagram Reviews | Explicit |
| REV-02 | Review supports comment, rating, like, and dislike actions. | Use-case diagram and class diagram | Explicit in figures; can like/dislike coexist, and who may review are unclear |
| REV-03 | Administrator can monitor reviews. | Brief | Explicit; moderation actions/criteria unspecified |
| PAY-01 | Payment is represented in the use-case diagram for Handyman, with Cash and Online variants; Online shows Credit Card and PayPal. | Use-case diagram and organigram | Explicit in figures; role, timing, processing, and whether operational in original release are unclear |

### 3.5 Administration and public/contact pages

| ID | Requirement | Source/evidence | Status |
|---|---|---|---|
| ADM-01 | Administrator can view an overview/list of users and user details. | Admin use-case diagram, class diagram | Explicit |
| ADM-02 | Administrator can block and unblock users. | Admin use-case diagram and class diagram | Explicit |
| ADM-03 | Administrator can list/view services and categories. | Admin prose and diagram | Explicit |
| ADM-04 | Administrator can add, edit, and delete services. | Brief and admin prose/diagram | Explicit |
| ADM-05 | Administrator can add, edit, and delete categories. | Brief and admin prose/diagram | Explicit |
| ADM-06 | Administrator can manage skills. | Brief | Explicit; skill CRUD operations not enumerated |
| ADM-07 | Administrator can monitor reviews. | Brief | Explicit; moderation operations unspecified |
| PUB-01 | The site provides About Us information. | Realization chapter/page screenshot | Explicit page; content requirements unspecified |
| PUB-02 | A user can send a message through Contact Us. | Realization chapter/page description | Explicit; recipient, storage, and response workflow unspecified |
| UX-01 | Web application is responsive and provides a fluid, usable experience. | Project brief | Explicit quality requirement |

## 4. Role-permission matrix

“Allowed” below reflects the report. “Open” means the report does not establish a safe, precise permission. Server-side enforcement is a **project constraint supplied by the user**, not something the report specifies technically.

| Capability | Homeowner | Handyman | Administrator |
|---|---|---|---|
| Register self | Yes | Yes | No self-registration specified |
| Log in / log out | Yes | Yes | Admin login described; logout implied by user/account module, exact admin flow not detailed |
| Edit own profile / change password / delete own account | User module says User; treat as account-owner actions, subject to confirmation | Same | Same; admin account lifecycle unspecified |
| Set/manage handyman skills | No | Set skills at signup; subsequent editing unclear | Manage skills (operations unclear) |
| Browse service catalog/categories | Yes | Search services is shown; catalog browsing unclear | View/manage catalog |
| Search services by category/service/price | Yes | Search services shown; filters conflict with “future” prose | No special search permission stated; admin overview exists |
| Search/list handymen, rating/location/likes | Yes | No | No |
| Create service request | Yes | Open: actor prose says yes, main flow says homeowner | No |
| See requests | Own requests and their offers are implied by dashboard | Matching-skill requests only (brief); request search/details shown | No global request-management permission stated |
| Submit offer | No | Yes | No |
| Accept/refuse offer | Yes, for own request | No | No |
| Accept/decline a request | No | Open/contradictory handyman use case | No |
| Contact/message | After acceptance; phone/WhatsApp/in-app channel references conflict/are unclear | After acceptance implied; messaging diagram includes handyman | No |
| Manage appointments | Schedule/accept/decline/reschedule in diagram | Schedule/accept/decline/reschedule in diagram | No |
| Submit rating/review | Yes, for handyman/service interaction implied | No | Monitor reviews |
| Like/dislike | Homeowner actor diagram | Not assigned | Not assigned |
| Payments | Not assigned in payment diagram | Payment actor is Handyman | No |
| Manage users/block/unblock | No | No | Yes |
| Manage categories/services | No | No | Yes |
| About Us / Contact Us | Contact page available to user | Contact page available to user | Contact page available to user |

**Authorization proposal [Proposal]:** Scope private records to their owning user and enforce role and ownership checks in backend/API policies. In particular, homeowners must only see/manage their own requests and offers; handymen must only see skill-matching eligible requests and their own offers/profile; administrative capabilities require an administrator role. Exact exceptions require approval if the report implies them.

## 5. Use-case-to-feature mapping

| Use case / diagram group | Feature mapping | Requirements |
|---|---|---|
| Account / connection | Signup, account activation, login, logout, edit profile, change password, delete account, forgot password | ACC-01–14 |
| Admin: manage app/users | User list/details, block/unblock | ADM-01–02 |
| Admin: manage projects/catalog | Service/category overview and CRUD | ADM-03–05 |
| Admin: skills/reviews | Skill administration and review monitoring | ADM-06–07 |
| Homeowner: consult catalog | Browse categories/services | CAT-01–03 |
| Homeowner: search handyman | List/search by rating, location, likes | CAT-04–05 |
| Homeowner: create request | Select category/service, fill details/date/location, optional photos | REQ-01–04 |
| Handyman: view/search requests | Skill-matched list/details; filtering is unresolved | CAT-06–07, REQ-05 |
| Handyman: create offer | Offer details and optional photos | REQ-06–08 |
| Homeowner: receive/choose offer | View offers and accept/refuse | REQ-09–10 |
| Handyman request acceptance/creation | Request creation and accept/decline | REQ-11–13; contradictory/open |
| Hire / direct exchange | Phone, WhatsApp, messaging, documents and pictures | COM-01–04 |
| Appointment | Schedule, accept, decline, reschedule | COM-04–05 |
| Review handyman | Comment, rate, like/dislike | REV-01–02 |
| Payment | Cash or online card/PayPal | PAY-01; scope/actor unclear |
| Public information/contact | About Us and Contact Us message | PUB-01–02 |

## 6. Database entity and relationship proposal

The list below reconstructs the report’s class diagram and adds only structural database decisions needed to represent those concepts. It is a proposal, not an approved schema. “Multiplicity” reflects the visible UML where legible; ambiguous cardinalities need confirmation.

| Entity | UML attributes shown | Relationship proposal |
|---|---|---|
| `User` | `user_id`, `username`, `fullName`, `password`, `email`, `type`, `CIN`, `phoneNumber` | Base identity for Homeowner and Handyman; report UML uses generalization. Account activation/status needed by workflow, but field is not shown. Never store a raw password [technical security proposal]. |
| `Homeowner` | `homeowner_id`, `address`, `city`, `geolocation` | User subtype/profile; owns requests. UML shows one homeowner to many Demandes. |
| `Handyman` | `handyman_id`, `address`, `city`, `rate`, `maritalStatus`, `birthday`, `picture`, `gender` | User subtype/profile; can have many skills; participates in offers and reviews. Some fields are shown but not justified by a use case. |
| `Admin` | `admin_id`, `address`, `city` | Admin identity/profile; report associates admin with user-management and catalog operations. Whether it inherits from User is visually/semantically unclear; project requirement establishes separate role. |
| `Category` | `category_id`, `categoryName` | One Category to one or more Services in UML. |
| `Service` | `service_id`, `serviceName`, `description` | Belongs to one Category; associated with Demande(s); associated to Skills in UML. Exact skill/service relation needs confirmation. |
| `Skills` | `skill_id`, `skillName`, `description` | Handymen have skills (diagram indicates many-to-many); skill/service association appears many-to-many or unclear from crossing lines. |
| `Demande` (Request) | `demande_id`, `title`, `creationDate`, `startDate` | Belongs to one Homeowner and one Service per visible links; has zero or more Offers. Description/location are required by prose but absent as UML attributes. |
| `Offer` | `offer_id`, `price`, `startDate`, `endDate`, `description` | Linked to a Demande and Handyman; can have optional picture(s), Messages, and at most one Review in diagram. UML cardinalities around Offer/Request are hard to interpret and should be reviewed. |
| `PictureOff` | `pictureOff_id`, `picture` | Picture attachment associated with an Offer and/or Demande; diagram reuses this class/label ambiguously. Consider a generic attachment relation only after confirming semantics. |
| `Reviews` | `review_id`, `comment`, `like`, `dislike`, `rate` | Associated with an Offer; diagram indicates optional review per Offer. Review target and uniqueness policy unspecified. |
| `Messages` | `message_id`, `content`, `document`, `order` | Associated with Offer and Demande in diagram. Participants, conversation ownership, timestamps, and attachment representation are not modeled. |
| `PictureSk` | `pictureSk_id`, `picture` | Related to Skills in diagram; exact cardinality is unclear. |
| Payment | No payment class in class diagram | Payment and methods appear only in use-case/organigram. Do not design payment persistence/integration until scope and workflow are confirmed. |
| Appointment | No appointment class in class diagram | Appointment behavior appears in diagram/methods. Do not finalize entity/constraints until lifecycle is clarified. |

**Relational/API-oriented technical proposals [Proposal]:**

- Represent role-specific information with a stable user identity plus role/profile records (or another equivalent normalized design), so authentication, ownership, and API identity remain consistent for future Flutter clients.
- Use explicit foreign keys for ownership and catalog links; enforce access checks in backend services/policies, not only the UI.
- Store uploaded files as private object/file references with authorization checks rather than embedding image/document bytes in relational rows. This is a storage proposal; report only says pictures/documents can be shared.
- Expose business operations through versioned JSON APIs independent of the website UI, allowing a future Flutter client to reuse the same authentication, validation, and authorization rules.
- Use secure password hashing, activation tokens, and server-side validation as standard implementation safeguards. These choices do not add user-facing features.

## 7. Contradictions and ambiguities requiring decisions

1. **Who creates requests?** Main brief, homeowner screens, and request sequence assign request creation to homeowners. Handyman prose and the use-case diagram also show handyman request creation.
2. **Offer versus accept/decline request:** Main workflow says handymen submit offers and homeowners accept/refuse. Diagram and prose also show handyman accepting/declining demands. The state transitions and intended distinction are not defined.
3. **Account activation versus 2FA:** The account module calls activation “2FA,” but describes an email activation link, not a second authentication factor.
4. **Messaging channel:** Requirements mention direct exchange after acceptance, phone number, WhatsApp, and an in-app messaging system with documents/pictures. It is unclear which is authoritative/required.
5. **Appointments:** Scheduling, acceptance, decline, and rescheduling appear in diagrams, but are absent from the brief, sequences, class model, and realization pages. No lifecycle, time rules, or ownership semantics are defined.
6. **Payments:** Cash, credit card, and PayPal appear in diagrams, but no payment entity, sequence, provider, fee, refund, or realization evidence appears. The use-case diagram associates payment with Handyman.
7. **Handyman request filtering:** Brief says handymen only see requests matching skills. Actor description says category/service/location filtering is future work, but the use-case diagram shows those searches.
8. **Admin taxonomy:** Brief says categories, services, skills, and review monitoring; actor text stresses services/categories; diagrams also show user blocking. Exact admin permissions for skills/reviews are not detailed.
9. **Admin identity:** Admin is a distinct class in UML; User has a `type` attribute, and the user-provided target requires an Administrator role. Inheritance/storage model is not settled.
10. **Data-model gaps:** Request description/location, account activation state, offer/request statuses, timestamps for messages, appointment details, payment records, and review author/target are not fully represented in UML.
11. **Profile attributes:** UML includes CIN, marital status, birthday, gender, geolocation, picture, rate, and address, but the report does not establish which are required, public, or editable.
12. **Likes/dislikes and rating:** Diagram shows rating plus separate like/dislike actions; no aggregation or validation rules are specified.
13. **Pictures:** `PictureOff` is connected around Request and Offer; `PictureSk` is linked to Skills. Cardinality and upload ownership are uncertain.
14. **Messages:** UML links Messages to both Request and Offer; it does not define parties, whether conversations start before acceptance, or document/picture handling.
15. **Contact Us:** The page allows a user to send a message, but recipient, data retention, and response path are absent.
16. **Account deletion/password reset:** These are listed or diagrammed but have no flow, confirmation, recovery, or data-retention rules.
17. **Offer dates and price:** UML fields are present but currency, date meanings, availability, and editing/cancellation behavior are unspecified.
18. **Geographic matching:** Location/geolocation is shown, but no distance, privacy, or matching rule is defined.

## 8. Explicitly documented versus implementation status unclear

### Clearly part of the described core

These are repeated in the project brief, realization chapter, or a concrete workflow: responsive website; homeowner/handyman registration; handyman skills; email activation; email/password login; service/category browsing; homeowner request with description, desired date, and optional photos; skill-matched requests; handyman offers; homeowner accept/refuse; post-acceptance direct contact; homeowner dashboard showing own requests/offers and profile/logout; admin category/service management; review/rating; user account profile/password management.

### Present in documentation, but first-release implementation status unclear

These are documented somewhere in the report, and therefore are not silently discarded, but lack a complete workflow or conflict with another section: 2FA; forgot password; self-service account deletion; handyman-created requests and accept/decline; handyman filtering; handyman discovery filters by rating/location/likes; in-app instant messages and picture/document sharing; appointment scheduling lifecycle; cash/card/PayPal payment; likes/dislikes independent of ratings; admin block/unblock and review moderation details; contact-message processing; some UML-only profile fields.

## 9. Proposed minimal implementation scope

This is a **scope proposal for approval**, not an authorization to implement. It keeps the report’s central homeowner–handyman service flow intact and avoids treating incomplete diagrams as permission to build complex integrations.

### Baseline for the first website increment

1. Three roles with separate protected experiences: Homeowner, Handyman, Administrator; secure authentication and backend-enforced role/ownership authorization.
2. Homeowner/Handyman signup, email activation, login/logout, profile/password management; capture only fields confirmed as necessary for the flow.
3. Admin-managed categories, services, and handyman skills, plus user list/details and block/unblock if confirmed as baseline admin operations.
4. Homeowner service discovery and request creation with description, requested start date, location, and optional photos.
5. Skill-matched handyman request list/details and offer submission with price, proposed dates, description, optional pictures.
6. Homeowner dashboard for own requests/offers and accept/refuse actions.
7. Contact exchange after acceptance, using the channel the owner approves (report references phone/WhatsApp/in-app messaging; choose only after resolving item 4 above).
8. Reviews and ratings after a completed/accepted service, subject to an approved eligibility rule; admin review monitoring.
9. About Us and Contact Us pages; contact form behavior to be confirmed.
10. Responsive, uncluttered web UI; backend business logic exposed through reusable APIs for a later Flutter client.

### Hold for explicit product decision before implementation

Do not silently omit these from the product specification; decide whether they belong in the initial release or a later increment: true second-factor authentication; password reset; account deletion; handyman-created requests and direct accept/decline; advanced discovery filters; in-app chat/document sharing; appointment scheduling; payments and provider integrations; like/dislike functionality; any UML-only personal fields not needed for an approved use case.

**Scope safeguard:** If an item is confirmed as a required original feature, it must be planned into an increment rather than dropped. The “minimal” proposal only sequences unclear/high-complexity items pending review; it does not redefine the source report.

## 10. Traceability and approval checklist

Before schema/migrations or pages are created, approve or clarify:

- Request creator and the intended meaning of handyman accept/decline.
- Email activation versus 2FA; whether forgot-password and account deletion are in scope.
- Post-acceptance channel: phone/WhatsApp link, in-app messages, or both; whether media/documents are required.
- Whether appointment scheduling and payment are first-release features; payment actor, methods, and completion flow.
- Which search filters are required at launch.
- Review eligibility, rating/like/dislike semantics, and moderation actions.
- Required profile fields and visibility/privacy rules.
- Admin role storage and exact powers, including user blocking and skill/review management.
- Contact Us message handling and retention.

**Approval gate:** This document is for review. No migrations, database tables, application pages, or application code should be created until the specification and unresolved scope decisions are approved.

---

# 11. Confirmed product scope and implementation plan

**Authority:** This section records the user's Final Product Requirements. Where it conflicts with an earlier “Open” item or proposed scope above, this confirmed decision takes precedence. Original report requirements remain traceable in Sections 3–8; user-confirmed additions and exclusions are identified here. This is still a plan for approval, not permission to start implementation.

## 11.1 Binding version-one decisions

| Topic | Confirmed version-one decision | Relationship to report |
|---|---|---|
| Request creation | Homeowners alone create service requests. Handymen never create homeowner requests. | Resolves the report's conflicting handyman request use case in favor of its main workflow, homeowner UI, and request sequence. |
| Activation | Email verification activates accounts. No 2FA. | Selects the email workflow; explicitly excludes interpreting the report's “2FA” label as a requirement. |
| Password recovery | Forgot-password is included. | The report use-case diagram includes it; workflow details remain to be implemented safely. |
| Reviews/ratings | Included in version one; a homeowner may review after the service is marked completed. | Repeatedly documented in report; completion-based eligibility is user-confirmed. Exact completion actor and edit policy remain open. |
| Account moderation | Administrator can block and unblock accounts. | Report use-case/class diagram. |
| Search | Search filters are included for services and handymen, and matching requests for handymen. Price filtering uses handyman offer prices, not a fixed service price or homeowner budget. | Filters appear in diagrams; user confirmed offer-price filtering. |
| Likes | Homeowners can favorite/like a handyman and remove that favorite later. This is separate from reviews. | User-confirmed meaning of “likes”; review like/dislike is not adopted for v1. |
| Communication | WhatsApp contact becomes available only after an offer is accepted. No internal chat. | Selects WhatsApp/phone contact over the in-app messaging diagram. Internal messaging, pictures/documents in chat are excluded. |
| Administrator | Platform owner with a private dashboard to manage users, see relevant account/profile information, block/unblock users, manage catalog and skills, monitor requests/offers/reviews/activity, and view useful statistics. | Extends report's admin service/category/user/review powers with user-confirmed activity monitoring and statistics. |
| Exclusions | No online payments, appointments/rescheduling, internal messaging, document sharing, handyman-created requests, or 2FA in v1. | Explicitly excluded by user even where report diagrams show these. Do not add unless later approved. |
| Technology | Laravel/PHP, MySQL, HTML/CSS/JavaScript/Bootstrap. Responsive website; API-ready backend for a possible Flutter client. | User-confirmed technical foundation; the report's historical stack is consistent. |

## 11.2 Updated role-permission matrix

| Capability | Homeowner | Handyman | Administrator / platform owner |
|---|---|---|---|
| Register and verify email | Register as homeowner; verify own email | Register as handyman; verify own email | Admin account provisioned through a controlled process (proposal; no public admin signup) |
| Authenticate | Login, logout, forgot password | Login, logout, forgot password | Protected, separate admin area; login/logout |
| Own profile/password | View and update own profile/password | View and update own profile/password, skills | View relevant homeowner/handyman account and profile information; manage accounts as authorized |
| Browse catalog | Browse categories/services | Browse relevant services/skills | Create, view, update, delete categories/services; manage skills |
| Search | Filter services and handymen; use likes | Filter skill-matching homeowner requests | Search/view records as needed for administration |
| Requests | Create, view and manage own requests; see attached request photos | View matching homeowner requests and photos; cannot create homeowner requests | Monitor platform requests; no edit/delete powers specified by user |
| Offers | Receive, compare, accept or refuse offers on own requests | Submit offers; track own submitted offers | Monitor offers; no edit/delete powers specified by user |
| Contact | Open WhatsApp contact for handyman after accepting an offer | Be contacted through WhatsApp after offer acceptance | No conversation access; there is no internal chat |
| Reviews and likes | Submit reviews/ratings; use likes feature | Receive reviews/ratings; cannot review own service as an assigned capability | Monitor reviews and platform activity; specific moderation actions beyond monitoring not specified |
| Account status | No blocking other users | No blocking other users | Block/unblock homeowners and handymen |
| Dashboard | Homeowner dashboard focused on own requests/offers/profile | Handyman dashboard focused on matching requests, own offers, profile/skills/reviews | Private control panel with user management, catalog/skill management, activity monitoring, and useful statistics |

**Authorization invariant:** All role, ownership, and account-status checks must execute on the server. Homeowners can access only their own private requests/offers/profile; handymen can access only their own profile/offers and requests allowed by skill matching and product rules; blocked accounts cannot use protected marketplace actions; administrative routes/data require the administrator role. These are user-confirmed security requirements, not just interface behavior.

## 11.3 Proposed relational schema (review draft; no migrations created)

This schema reconciles the class diagram with the confirmed workflows. Names are proposed English equivalents for report names. It preserves the UML's business entities while using relational structures suitable for MySQL. Final field constraints, status values, deletion policy, and exact like/search semantics require review.

| Proposed table | Key fields / purpose | Relationships and traceability |
|---|---|---|
| `users` | `id`, role (`homeowner`/`handyman`/`admin`), name fields, username, email, password hash, phone, email-verified timestamp, account status (`active`/`blocked` etc.) | Shared identity for report `User`, with role and verified/blocked state needed by confirmed auth/admin workflows. Do not store plaintext passwords. Whether Admin uses same table is a technical proposal. |
| `homeowner_profiles` | `user_id`, address, city, optional geolocation fields | One-to-one with user; report `Homeowner`. Owns many service requests. |
| `handyman_profiles` | `user_id`, address, city, optional picture and report profile fields | One-to-one with user; report `Handyman`. Has skills and receives offers/reviews. Report fields such as CIN, marital status, birthday, gender and geolocation are not automatically required; confirm collection/visibility first. |
| `categories` | `id`, name | Report `Category`; one category has many services. |
| `services` | `id`, `category_id`, name, description | Report `Service`; belongs to a category; referenced by requests. No service price or homeowner budget field is proposed: the confirmed price filter uses prices stored on `offers`. |
| `skills` | `id`, name, description | Report `Skills`; administrator manages. |
| `handyman_skill` | `handyman_id`, `skill_id` | Many-to-many between handyman profiles and skills, as UML indicates; supports skill-matched request visibility. |
| `service_skill` | `service_id`, `skill_id` | Proposed mapping to express which skills qualify for a service. UML lines suggest service-skill association, but exact mapping/cardinality should be confirmed. |
| `service_requests` | `id`, `homeowner_id`, `service_id`, title, description, location/address, city, desired start date, status, created/updated timestamps | Report `Demande`; belongs to one homeowner and one service; has optional photo attachments and zero or more offers. Statuses need approval. Only homeowners can create. |
| `request_photos` | `id`, `request_id`, private file key/path, metadata | Represents optional photos in request sequence / `PictureOff`. Private access controlled by request ownership and eligible handyman access. |
| `offers` | `id`, `request_id`, `handyman_id`, price, start date, end date, description, status, timestamps | Report `Offer`; one request has zero or more offers; each offer belongs to one request and one handyman. Homeowner accepts/refuses; handyman tracks own offers. This is the sequence diagram's create-offer flow. |
| `offer_photos` | `id`, `offer_id`, private file key/path, metadata | Optional offer pictures from offer sequence / picture UML. |
| `reviews` | `id`, `offer_id`, `homeowner_id`, `handyman_id`, rating, comment, timestamps | Report `Reviews`; proposed one review per eligible accepted/completed offer pending eligibility decision; administrator can monitor. Link to offer establishes context and ownership. |
| `handyman_favorites` | `id` (or composite key), `homeowner_id`, `handyman_id`, `created_at` | Approved meaning: a homeowner favorites a handyman. Enforce one favorite per homeowner/handyman pair with a unique constraint; allow removal (unfavorite). This is separate from reviews. Do not implement review likes/dislikes in v1. |
| Admin activity log | Candidate actor, action, record type/id, timestamp | Proposed only if “monitoring platform activity” means an auditable event history. Clarify what activity is visible, retention, and privacy before creating. |

### Relationship summary

- User has one role-specific profile; Homeowner → many Requests; Handyman ↔ many Skills.
- Category → many Services; Service → many Requests.
- Request → many Photos and many Offers; each Offer → one Handyman and one Request.
- Offer → many optional photos and zero/one Review (proposed cardinality; confirm review policy).
- Admin manages users, categories, services, and skills and monitors requests, offers, reviews, and activity.
- No tables for appointments, internal messages, document sharing, or payments in v1, because those are expressly excluded.

**File-storage proposal:** Put photo bytes in private file/object storage and store only authorized file references/metadata in MySQL. The backend must authorize every upload/download. Exact hosting and storage provider are not selected here.

## 11.4 Proposed application architecture

The following is a proposal for review, not a locked architectural decision.

- **Web presentation:** Laravel-rendered pages using Blade and Bootstrap for the first website; JavaScript for interactive behavior where needed. Keep role-specific page shells/navigation simple and responsive.
- **Application/business layer:** Laravel controllers handle HTTP concerns; request validation, authorization policies, and service/action classes enforce marketplace workflows (registration, activation, request creation, offer submission/decision, reviews, likes, admin catalog/user actions). Keep business rules out of templates.
- **Persistence:** MySQL with migrations, Eloquent models, foreign keys, indexes for owner/status/service/skill search, and explicit relationship constraints. Build only after schema and open decisions are approved.
- **Authentication/security:** Laravel authentication with email verification and forgot-password flow; hashed passwords, CSRF protection for web forms, rate limits for login/reset, server validation, and policy checks for role, record ownership, matching, and blocked status. Admin account provisioning and password recovery follow protected operations.
- **API readiness:** Put reusable workflows behind application services and expose a documented, versioned JSON API (for example `/api/v1`) alongside web routes. Keep the same validation, authorization, and business rules for web and future Flutter clients. Choose a token/session strategy when deciding the API architecture; do not duplicate marketplace logic in a mobile-specific layer.
- **WhatsApp:** After acceptance, present an authorized WhatsApp link/action using the handyman contact number. Do not persist internal conversation messages or expose unrelated users' private numbers. Confirm number visibility and exact link behavior before finalizing.
- **Admin dashboard:** Protected admin routes and policies; focused user lookup/blocking, catalog/skill management, request/offer/review monitoring, and agreed summary statistics. “Useful statistics” requires an approved metric list; no fabricated analytics.

## 11.5 Requirement-to-implementation traceability

| Confirmed requirement | Report source | Planned module/feature | Verification evidence during implementation |
|---|---|---|---|
| Homeowner/handyman registration, email verification, login/logout, profile/password | Account module, signup/login descriptions, User UML | Authentication and profile module | Registration/verification/recovery flows work; wrong role and unverified/blocked access rejected server-side |
| Forgot password | Use-case diagram | Auth recovery flow | Recovery request and token flow work without exposing account existence |
| Categories/services browse/search | Brief, service page, use case, Category/Service UML | Catalog and discovery | Search returns matching catalog data; admin changes reflected |
| Homeowner request with description/location/date/photos | Brief, request page, sequence | Request module | Request persists with selected service; optional photos upload/access checks work |
| Homeowner-only request creation | Confirmed decision; report primary flow | Request authorization | Handyman cannot create through UI or direct HTTP/API request |
| Skill-matched handyman request discovery and filters | Brief; use case; handyman actor text | Matching/search module | Handyman sees only eligible records; filters operate on allowed set |
| Offer with price/dates/description/photos | Brief, offer sequence, Offer UML | Offer module | Offer persists against eligible request and is visible to owner and submitting handyman only |
| Compare/accept/refuse offers; track submitted offers | Brief, dashboard, confirmed requirements | Offer decision/dashboard module | Owner can decide only on own request; handyman sees own offers; state transitions enforced |
| WhatsApp after accepted offer | Brief and homeowner use case; confirmed channel | Contact action | WhatsApp action appears only for accepted offer and authorized participants |
| Reviews/ratings and likes | Brief, use-case and Reviews UML; confirmed likes | Feedback module | Eligibility, ownership, one-like/rating rules validated after decisions; admin can monitor reviews |
| Admin user/profile view and block/unblock | Admin use-case/class UML | User administration | Admin-only controls; blocked account is denied protected actions; audit expectations confirmed |
| Admin category/service/skill management | Brief, Admin UML | Catalog/skill admin | Create/edit/delete behavior and referential constraints work |
| Admin monitor requests/offers/reviews/activity and statistics | Confirmed user requirements | Admin oversight dashboard | Approved data and metrics are derived from persisted records, not static placeholders |
| Responsive website and Flutter-ready backend | Brief and confirmed technical foundation | Shared layout + application/API layers | Responsive pages; web/API operations reuse same policy and business services |
| Excluded v1 features | User-confirmed exclusions | No v1 modules/routes/tables | No payment, appointments, internal chat, document sharing, handyman-created requests, or 2FA workflows shipped |

## 11.6 Proposed implementation phases

No phase begins until this plan is approved. Each phase includes functional checks before proceeding, consistent with the user's requested incremental development and the report's V-model emphasis.

1. **Specification and design approval:** Resolve open product rules below; approve relationships, statuses, search behavior, admin metrics, and privacy rules. Produce final schema/API/role-policy design.
2. **Foundation and authentication:** Laravel project setup, MySQL configuration, shared user/role foundation, registration, email verification, login/logout, forgot password, protected role routes, authorization policies, and base responsive layout.
3. **Administrator foundation:** Private admin dashboard; user/profile lookup and block/unblock; category, service, and skill management. Include operational activity view and approved baseline statistics only.
4. **Catalog and discovery:** Public/authenticated catalog behavior as approved; homeowner service/handyman search filters; handyman skill matching and request filters; server-side scoping.
5. **Homeowner requests and handyman offers:** Request creation and photos; handyman matching list/details; offer submission/photos; homeowner request/offers dashboard; compare and accept/refuse; handyman submitted-offer tracking.
6. **WhatsApp and feedback:** Authorized post-acceptance WhatsApp contact; reviews/ratings; likes behavior; admin review monitoring.
7. **Completion and release readiness:** About Us/Contact Us behavior, accessibility/responsive pass, security/privacy review, API documentation, end-to-end acceptance checks, deployment configuration, and final defect fixes.

## 11.7 Confirmed refinements and remaining decisions

The following four product decisions are now confirmed and supersede any earlier open wording:

1. **Likes mean favorites.** A homeowner can favorite a handyman and later remove that favorite. This is a separate relationship from reviews. Review likes/dislikes are not part of v1.
2. **Price filtering uses offer prices.** Search/filter results use prices submitted by handymen in `offers`. Do not add a fixed service price or homeowner request-budget field for this feature unless separately approved.
3. **Review eligibility is completion-based.** A homeowner may review only after the service is marked completed. The exact actor/action that marks completion, and whether reviews can be edited or deleted, still need a rule.
4. **WhatsApp is available after acceptance only.** The handyman's WhatsApp contact action/number is revealed to the homeowner only after that homeowner accepts an offer for the request. No internal messages are stored.

### Decisions that genuinely affect the database design

These are the remaining items to settle before migrations. The specification can continue at the conceptual level, but final columns, constraints, and foreign-key/delete behavior depend on them.

1. **Offer lifecycle and acceptance rule:** Confirm the exact offer statuses and allowed transitions; whether a homeowner can accept only one offer per request; and whether other pending offers are automatically closed when one is accepted. This determines status values, transaction rules, and how request/offer state is represented.
2. **Completion and review lifecycle:** Confirm who marks a service completed (homeowner, handyman, or both/confirmation), and whether one review per accepted offer is allowed. Completion determines when a review row becomes valid and which state transition authorizes it.
3. **Request lifecycle:** Confirm whether homeowners can edit or cancel requests, and what happens to a request after an offer is accepted or work is completed. This affects request status values and whether records can be deleted or must be retained.
4. **Profile fields and privacy:** Approve the actual fields to collect and which are public/private. In particular, decide whether CIN, birthday, gender, marital status, exact address/geolocation, and username are needed. This determines columns, nullability, and what information can be exposed in search/profile APIs.
5. **Service-to-skill mapping:** Confirm whether each service is associated with one or more required skills and whether a handyman may hold multiple skills. The proposed `service_skill` and `handyman_skill` relationships depend on this matching rule.
6. **Account deletion and blocked-account behavior:** Confirm whether users can permanently delete their accounts, or whether deactivation/anonymization is required to preserve requests, offers, and reviews. Also specify what blocked users can still access. This affects foreign-key deletion policies, retention, and authorization.

### Remaining choices that do not block the core schema

These can be decided during implementation planning or before the relevant module, without holding up the core marketplace schema:

- **Admin statistics:** Start with database-derived counts for homeowners, handymen, requests, offers, reviews, and blocked accounts, with a selectable time period for activity where useful. Confirm the exact dashboard presentation later.
- **Admin activity history:** The admin can monitor current platform records. A separate audit-log table is only needed if you want a durable history of who changed what and when; decide the event list and retention before adding it.
- **Admin provisioning:** Use a controlled, non-public process to create the initial administrator account. This is an operational setup decision, not a marketplace schema blocker.
- **Contact Us:** Decide recipient and notification handling before building the contact workflow. A stored contact-message table is needed only if messages are to be retained in BreIn.
- **Photo limits:** File formats, size/count limits and retention can be set before implementing uploads; they do not materially change the core entity relationships.
- **WhatsApp link format:** Whether the link opens a plain chat or includes a prefilled message can be decided during UI implementation. The access rule is already fixed: only after acceptance.

## 11.8 Approval gate

The four product decisions above are incorporated. Before creating migrations, settle the six database-impacting decisions in Section 11.7: offer lifecycle/one-acceptance rule, completion/review lifecycle, request lifecycle, profile fields/privacy, service-to-skill mapping, and account deletion/block behavior. The remaining non-blocking choices can be finalized in their respective implementation phases. The schema, architecture, and seven phases remain proposals until approved. No source code, migrations, database tables, application pages, or tests have been created or run.