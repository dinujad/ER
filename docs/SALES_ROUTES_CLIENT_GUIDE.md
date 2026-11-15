# DERMAS ERP — Sales Routes Client Guide

**System:** DERMAS ERP (`https://erp.dermasapparel.com`)  
**Audience:** Admin / office staff and field sales persons  
**Language:** Sinhala explanation · screen buttons are in **English** as they appear in the system

This document explains how office staff create sales routes, how sales persons use them on the road, and what each person can see and do.

---

## 1. What a “Sales Route” is

A **Sales Route** is a named field area — for example `Colombo North` or `Kandy Town`.

It is **not** a Google Map. Admin does not type From / To points. Admin only gives the route a name, optionally a short code, and assigns one or more sales persons to it.

When a sales person starts work that day, they press **Start route**, enter the **driver** and **assistants** for that trip, and the system records:

- start time
- end time (when they press **Complete route**)
- how long the trip took
- expenses added during that trip (fuel, food, etc.)

**Simple picture**

```
Office (Admin)                         Field (Sales Person)
─────────────────                      ────────────────────
1. Create route name                   1. Login to Sales portal
2. Assign sales person(s)              2. See “My Route”
3. Watch who has started               3. Enter driver + assistants
4. Review trip + expenses              4. Start route
                                       5. Add orders / expenses
                                       6. Complete route at end of day
```

**Important rules**

| Rule | Meaning |
|------|---------|
| One route per sales person | A person can be on only one route at a time. Assigning them to a new route moves them off the old one. |
| One active trip at a time | They must **Complete** the current trip before starting another. |
| Same-route sharing | Sales persons on the **same** route can see each other’s **customers**. Orders/sales still belong to the person who created the order. |
| Inactive route | If admin turns the route off, the sales person cannot start a new trip until it is Active again. |

---

## 2. Admin — how to create a route

### 2.1 Open the screen

1. Login to the ERP as Admin (or a user with Sales Persons permission).
2. Left menu → **Routes**.
3. Click **Manage routes**.

You will see the list of all routes: Name, Code, how many sales persons are assigned, Active / Inactive.

Buttons on this page:

| Button | What it does |
|--------|----------------|
| **Add Route** | Create a new route |
| **Started routes** | Who is currently on a trip right now |
| **All routes** | History of started and completed trips |
| **Edit** | Change name, code, people, active status |
| **Delete** | Remove the route (only if nobody is assigned and there is no trip history) |

### 2.2 Create the route (Add Route)

Click **Add Route**. Fill:

| Field | Required? | Example | Notes |
|-------|-----------|---------|--------|
| **Route name** | Yes | Colombo North | This is what the sales person sees as “My assigned route”. |
| **Code** | No | R-COL | Short internal code. Can leave empty. |
| **Description** | No | Covering Kiribathgoda, Wattala | For office notes only. |
| **Active** | Default: ticked | — | Untick later if you want to stop new trips without deleting the route. |
| **Assign sales persons** | No | Tick Kamal, Nimal | You can save the route with nobody assigned, then assign later. |

Under **Assign sales persons**:

- Type in the search box to find by **name** or **phone**.
- Tick the people who should work this area.
- If someone already belongs to another route, the list shows `(currently on another route)`. Ticking them **moves** them to this new route.

Click **Save Route**.

### 2.3 Other ways to assign a person to a route

You do **not** have to assign people only on the Add Route screen. Any of these work:

**A. While creating the sales person**

1. Left menu → **Sales Persons** → **Add Sales Person**.
2. Fill name, email, phone.
3. **Sales Route** dropdown → pick the route.
4. Save. Login SMS goes to their phone.

**B. From the sales person list (fastest for one person)**

1. **Sales Persons** list.
2. On that row click **Assign Route**.
3. Choose the route (or **— No route —** to remove).
4. **Save Route**.

**C. Edit sales person**

1. **Sales Persons** → **Edit**.
2. Change **Sales Route**.
3. Save.

**D. Edit the route itself**

1. **Routes** → **Manage routes** → **Edit**.
2. Tick / untick people.
3. **Update**.

### 2.4 Edit, pause, or delete a route

**Edit** — change name, code, description, who is assigned, Active tick.

**Pause (recommended instead of delete)** — Edit → untick **Active** → **Update**.  
The route stays in history. Sales persons cannot start a **new** trip on it.

**Delete** — only allowed when:

- no sales person is still assigned, **and**
- there is no trip history.

If the system refuses delete, either reassign the people, or just mark the route **Inactive**.

---

## 3. Admin — what you can watch after routes exist

### 3.1 Started routes (live)

**Routes → Started routes**

This is the “who is on the road now” screen.

You see, for each active trip:

- Sales person name
- Route name
- Driver name (typed by the sales person that morning)
- Assistants
- Start time
- Duration so far
- Expense total so far
- **View** — full trip detail + expense slips

Use this during the day to confirm the team actually started, and who is with them.

### 3.2 All routes (history)

**Routes → All routes**

Filter:

| Filter | Options |
|--------|---------|
| **Status** | All · Active (started) · Completed |
| **Sales person** | One person, or all |

Each row: start time, complete time, duration, driver, assistants, expense total, **View**.

**View** opens the trip page: status, driver, assistants, route, and every expense with receipt (if they uploaded a slip).

### 3.3 Sales person expenses (office)

Two places:

1. Left menu → **Expenses** → **Sales Person Expenses** — list with filters, receipts.
2. **Reports** → **Sales Person Expenses Report** — PDF / CSV for a date range, person, type, route.

Expenses added **after Start route** are linked to that trip. Office can see them on the trip **View** page and on the expense report.

---

## 4. Sales person — how they use the route

Sales persons do **not** use the Admin ERP menu. They login to the **Sales portal**.

Typical login page: `https://erp.dermasapparel.com/saleperson/login`  
(Email + password sent by SMS when admin created the account.)

After login, left menu includes:

| Menu | Purpose |
|------|---------|
| **Dashboard** | Summary, today’s route, quick order, expenses |
| **My Route** | Start / complete the day’s trip |
| **Orders** | Create and view orders |
| **Sale Returns** | Returns |
| **My Expenses** | Monthly expense list |
| **Sales & Commission** | Their sales and commission |
| **Add Customer** / **Customer List** | Customers (own + same-route teammates) |

### 4.1 Before they can start

Admin must have:

1. Created the route.
2. Assigned this sales person to it.
3. Left the route **Active**.

If not assigned, **My Route** shows **No route assigned** — they must ask office.

If assigned but Inactive, they see a warning: ask office to activate.

### 4.2 Start the route (morning)

Open **My Route** (or the **Today’s route** card on **Dashboard**).

Fill:

| Field | Required? | Example |
|-------|-----------|---------|
| **Driver name** | Yes | Sunil |
| **Assistants** | No | Kamal, Nimal |

Assistants is free text. They can type several names, usually comma separated.

Click **Start route**.

The system then:

- records start time (Sri Lanka time)
- shows a live duration
- shows Driver / Assistants for this trip
- unlocks trip expenses on **My Route**

They **cannot** start a second trip until they **Complete** this one.

### 4.3 During the day — what they can do

While the route is started, they can use the portal as usual:

| Option | Where | Notes |
|--------|-------|--------|
| Create order | **Orders** → **New Order**, or Dashboard product search | Normal selling |
| Add / view customers | **Add Customer**, **Customer List** | Same-route colleagues’ customers are also visible |
| Add trip expense | **My Route**, **Dashboard**, or **My Expenses** | If a trip is started, the expense is attached to **this trip** |
| See trip expenses | **My Route** | Running total for today |
| Complete the trip | **My Route** or **Dashboard** → **Complete route** | End of day |

**Add expense** fields:

| Field | Required? | Options / notes |
|-------|-----------|-----------------|
| **Date** | Yes | Defaults to today |
| **Type** | Yes | Fuel · Travel / transport · Food · Accommodation · Supplies · Others |
| **Description** | Yes | e.g. Diesel, lunch |
| **Amount (Rs)** | Yes | Must be more than 0 |
| **Slip** | No | Photo or PDF of the bill. JPG, PNG, PDF. Max 10 MB. Phone camera works. |
| **Notes** | No | Extra comment |

Click **Save expense**. They stay on the same page. They can add many expenses on one trip.

They can also remove an expense from **My Expenses** if they entered it by mistake.

### 4.4 Complete the route (evening)

On **My Route** or **Dashboard**, click **Complete route**, then confirm.

The system records end time and duration (example: `8h 15m`).

After complete:

- they can start a new trip the next day (or later the same day if needed)
- that trip stays in **Recent trips**
- office can see it under **All routes**

### 4.5 Recent trips

Bottom of **My Route**: last trips with route name, driver, assistants, start time, duration, Active / Completed, expense total.

---

## 5. Suggested daily routine

### Office / Admin

| When | Action |
|------|--------|
| Once (setup) | Create routes by area. Assign each sales person. |
| When staff change | Use **Assign Route** on the Sales Persons list. |
| During the day | Open **Started routes** — confirm they started and who the driver is. |
| End of day / week | **All routes** + **Sales Person Expenses** — check duration, slips, totals. |

### Sales person

| When | Action |
|------|--------|
| Morning, before visits | **My Route** → driver + assistants → **Start route** |
| During visits | Orders, customers, expenses (with slip photo if possible) |
| After last visit | **Complete route** |

---

## 6. Common questions

**Q: Can two sales persons share one route?**  
Yes. Tick both when creating/editing the route. They will see each other’s customers. Each person’s orders still belong to them.

**Q: Can one sales person be on two routes?**  
No. The latest assignment replaces the previous one.

**Q: Who types the driver — office or sales person?**  
Sales person, at **Start route**. Driver and helpers can change every day, so office does not enter them on the route master.

**Q: What if they forget to Start?**  
Trip time is not recorded. Expenses they add from Dashboard / My Expenses still save, but they may not be linked to a trip. Ask them to Start before the first visit.

**Q: What if they forget to Complete?**  
The trip stays **Active**. Office will still see them on **Started routes**. They cannot start the next day’s trip until they Complete (or office follows up).

**Q: What if office removes them from the route while they are already started?**  
They can still **Complete** that trip. They cannot start a new one until they are assigned to an Active route again.

**Q: Why can’t we delete a route?**  
People are still assigned, or old trips exist. Mark it **Inactive** instead. History must stay for expenses and duration.

**Q: Does Start/Complete replace orders?**  
No. Route start/complete is only attendance + trip expenses. Selling is still **Orders**.

---

## 7. Quick button map

### Admin ERP

| Go here | Click | Result |
|---------|--------|--------|
| **Routes → Manage routes** | **Add Route** | New area name + assign people |
| **Routes → Manage routes** | **Edit** | Change people or pause (Inactive) |
| **Routes → Started routes** | **View** | Live trip + expenses |
| **Routes → All routes** | Filter + **View** | Past trips |
| **Sales Persons** | **Assign Route** | Put one person on a route |
| **Expenses → Sales Person Expenses** | Filters | All field expenses / slips |
| **Reports → Sales Person Expenses Report** | PDF / CSV | Printable summary |

### Sales portal

| Go here | Click | Result |
|---------|--------|--------|
| **My Route** or **Dashboard** | **Start route** | Trip clock starts |
| **My Route** / **Dashboard** / **My Expenses** | **Save expense** | Fuel, food, etc. |
| **My Route** or **Dashboard** | **Complete route** | Trip clock stops |

---

## 8. Setup checklist (first week)

1. Create routes for each selling area (**Add Route**).
2. Confirm every sales person appears under **Sales Persons**.
3. Assign each person to exactly one route.
4. Confirm each route is **Active**.
5. Ask one sales person to login → **My Route** → **Start route** with a test driver name → add a Rs 1 test expense with a photo → **Complete route**.
6. Office opens **Started routes** (during the test) then **All routes → View** and confirms driver, time, and slip.

If that test works, the team can use the same steps every working day.
